<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home(): JsonResponse
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')
            ->withCount(['products' => fn ($q) => $q->active()])->get();

        $featured = Product::active()->where('is_featured', true)->with(['variants', 'category'])
            ->orderByDesc('sold_count')->take(10)->get();

        $newest = Product::active()->with(['variants', 'category'])->latest()->take(10)->get();

        $bestsellers = Product::active()->with(['variants', 'category'])->orderByDesc('sold_count')->take(10)->get();

        $sale = Product::active()->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'base_price')
            ->with(['variants', 'category'])->orderByDesc('sold_count')->take(10)->get();

        return response()->json(compact('categories', 'featured', 'newest', 'bestsellers', 'sale'));
    }

    public function categories(): JsonResponse
    {
        return response()->json(
            Category::where('is_active', true)->orderBy('sort_order')
                ->withCount(['products' => fn ($q) => $q->active()])->get()
        );
    }

    /** Multi-value query param: ?brand=A&brand=B or ?brand[]=A or ?brand=A,B */
    protected function many(Request $request, string $key): array
    {
        $raw = $request->query($key);
        if ($raw === null || $raw === '') {
            return [];
        }
        $values = is_array($raw) ? $raw : explode(',', (string) $raw);

        return array_values(array_filter(array_map('trim', $values), fn ($v) => $v !== ''));
    }

    protected function applyFilters(Builder $query, Request $request, array $except = []): Builder
    {
        if (! in_array('q', $except) && ($search = trim((string) $request->query('q')))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if (! in_array('category', $except) && ($cats = $this->many($request, 'category'))) {
            $query->whereHas('category', fn ($q) => $q->whereIn('slug', $cats)->orWhereIn('id', $cats));
        }

        if (! in_array('color', $except) && ($colors = $this->many($request, 'color'))) {
            $query->whereHas('variants', fn ($q) => $q->whereIn('color', $colors));
        }

        if (! in_array('size', $except) && ($sizes = $this->many($request, 'size'))) {
            $query->whereHas('variants', fn ($q) => $q->whereIn('size', $sizes));
        }

        if (! in_array('pack', $except) && ($packs = $this->many($request, 'pack'))) {
            $query->whereHas('variants', fn ($q) => $q->whereIn('pack_size', array_map('intval', $packs)));
        }

        if ($request->filled('min_price')) {
            $query->whereHas('variants', fn ($q) => $q->where('price', '>=', (float) $request->query('min_price')));
        }
        if ($request->filled('max_price')) {
            $query->whereHas('variants', fn ($q) => $q->where('price', '<=', (float) $request->query('max_price')));
        }

        if (! in_array('discount', $except) && ($ranges = $this->many($request, 'discount'))) {
            $query->where(function ($q) use ($ranges) {
                foreach ($ranges as $range) {
                    [$min, $max] = array_pad(explode('-', $range), 2, null);
                    $q->orWhere(fn ($w) => $this->discountBetween($w, (int) $min, $max === null || $max === '' ? null : (int) $max));
                }
            });
        }

        if (! in_array('status', $except)) {
            $statuses = $this->many($request, 'status');
            if ($request->has('in_stock') && $request->query('in_stock') !== '') {
                $statuses[] = $request->boolean('in_stock') ? 'in_stock' : 'preorder';
            }
            if ($statuses) {
                $query->where(function ($q) use ($statuses) {
                    foreach ($statuses as $status) {
                        $q->orWhere(fn ($w) => $this->statusScope($w, $status));
                    }
                });
            }
        }

        return $query;
    }

    protected function discountBetween(Builder $q, int $min, ?int $max): void
    {
        // discount % = (compare_price - base_price) / compare_price * 100
        $q->whereNotNull('compare_price')->where('compare_price', '>', 0)
            ->whereRaw('(compare_price - base_price) * 100 >= compare_price * ?', [$min]);
        if ($max !== null) {
            $q->whereRaw('(compare_price - base_price) * 100 < compare_price * ?', [$max]);
        }
    }

    protected function statusScope(Builder $q, string $status): void
    {
        match ($status) {
            'new' => $q->where('created_at', '>=', now()->subDays(30)),
            'sale' => $q->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'base_price'),
            'featured' => $q->where('is_featured', true),
            'in_stock' => $q->whereHas('variants', fn ($v) => $v->where('stock', '>', 0)),
            'preorder' => $q->where('allow_backorder', true)->whereDoesntHave('variants', fn ($v) => $v->where('stock', '>', 0)),
            default => $q->whereRaw('1 = 0'),
        };
    }

    public function products(Request $request): JsonResponse
    {
        $query = $this->applyFilters(Product::active()->with(['variants', 'category']), $request);

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            'newest' => $query->latest(),
            'name' => $query->orderBy('name'),
            'discount' => $query->orderByRaw('CASE WHEN compare_price > base_price THEN (compare_price - base_price) / compare_price ELSE 0 END DESC'),
            'popular' => $query->orderByDesc('sold_count'),
            default => $query->orderByDesc('is_featured')->orderByDesc('sold_count'),
        };

        $products = $query->paginate((int) $request->query('per_page', 12))->withQueryString();

        return response()->json($products);
    }

    /**
     * Facets with counts. Each facet is counted against the other active
     * filters (not its own) so the numbers stay meaningful while filtering.
     */
    public function filters(Request $request): JsonResponse
    {
        $base = fn (array $except = []) => $this->applyFilters(Product::active(), $request, $except);
        $ids = fn (array $except = []) => $base($except)->select('id');

        $variantFacet = function (string $column, array $except, ?string $extra = null) use ($ids) {
            return ProductVariant::query()->whereIn('product_id', $ids($except))->where('is_active', true)
                ->whereNotNull($column)->where($column, '!=', '')
                ->toBase()->selectRaw($column.($extra ? ", {$extra}" : '').', COUNT(DISTINCT product_id) as count')
                ->groupBy($column)->orderBy($column)->get();
        };

        $discountRanges = [];
        foreach ([[0, 10, '10% хүртэл'], [10, 20, '10% – 20%'], [20, 30, '20% – 30%'], [30, 50, '30% – 50%'], [50, null, '50% ба дээш']] as [$min, $max, $label]) {
            $count = (clone $base(['discount']))->where(fn ($q) => $this->discountBetween($q, $min, $max))->count();
            $discountRanges[] = ['value' => $min.'-'.($max ?? ''), 'label' => $label, 'count' => $count];
        }

        $statuses = [];
        foreach ([['new', 'Шинэ'], ['sale', 'Хямдралтай'], ['featured', 'Онцлох'], ['in_stock', 'Бэлэн байгаа'], ['preorder', 'Урьдчилсан захиалга']] as [$value, $label]) {
            $statuses[] = ['value' => $value, 'label' => $label, 'count' => (clone $base(['status']))->where(fn ($q) => $this->statusScope($q, $value))->count()];
        }

        return response()->json([
            'categories' => Category::where('is_active', true)->orderBy('sort_order')
                ->withCount(['products' => fn ($q) => $this->applyFilters($q->active(), $request, ['category'])])->get(['id', 'name', 'slug', 'icon']),
            'colors' => $variantFacet('color', ['color'], 'MIN(color_hex) as color_hex')->map(fn ($r) => ['color' => $r->color, 'color_hex' => $r->color_hex, 'count' => $r->count]),
            'sizes' => $variantFacet('size', ['size']),
            'packs' => ProductVariant::query()->whereIn('product_id', $ids(['pack']))->where('is_active', true)
                ->toBase()->selectRaw('pack_size, MIN(pack_label) as label, COUNT(DISTINCT product_id) as count')->groupBy('pack_size')->orderBy('pack_size')->get(),
            'discounts' => $discountRanges,
            'statuses' => $statuses,
            'price' => [
                'min' => (float) (ProductVariant::whereIn('product_id', Product::active()->select('id'))->min('price') ?? 0),
                'max' => (float) (ProductVariant::whereIn('product_id', Product::active()->select('id'))->max('price') ?? 0),
            ],
            'total' => $base()->count(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::active()->with(['variants' => fn ($q) => $q->where('is_active', true), 'category'])
            ->where('slug', $slug)->firstOrFail();

        $product->increment('views');

        $related = Product::active()->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)->with(['variants', 'category'])->take(4)->get();

        return response()->json(['product' => $product, 'related' => $related]);
    }
}
