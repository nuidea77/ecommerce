<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category:id,name', 'variants']);

        if ($q = trim((string) $request->query('q'))) {
            $query->where('name', 'like', "%$q%");
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->query('status') === 'active');
        }
        if ($request->boolean('low_stock')) {
            $query->whereHas('variants', fn ($v) => $v->where('stock', '<=', 3));
        }

        return response()->json($query->latest()->paginate((int) $request->query('per_page', 15))->withQueryString());
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load(['category', 'variants']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->slug($data['name']);

        $product = DB::transaction(function () use ($data) {
            $variants = $data['variants'] ?? [];
            unset($data['variants']);
            $product = Product::create($data);
            $this->syncVariants($product, $variants);

            return $product;
        });

        return response()->json($product->load(['category', 'variants']), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validated($request, $product);
        if ($data['name'] !== $product->name) {
            $data['slug'] = $this->slug($data['name'], $product->id);
        }

        DB::transaction(function () use ($product, $data) {
            $variants = $data['variants'] ?? null;
            unset($data['variants']);
            $product->update($data);
            if ($variants !== null) {
                $this->syncVariants($product, $variants);
            }
        });

        return response()->json($product->fresh()->load(['category', 'variants']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['ok' => true]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:4096']]);
        $path = $request->file('image')->store('products', 'public');

        return response()->json(['url' => '/storage/'.$path]);
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:190'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'max:1000'],
            'specs' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'allow_backorder' => ['boolean'],
            'backorder_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:64'],
            'variants.*.color' => ['nullable', 'string', 'max:64'],
            'variants.*.color_hex' => ['nullable', 'string', 'max:9'],
            'variants.*.size' => ['nullable', 'string', 'max:64'],
            'variants.*.pack_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'variants.*.pack_label' => ['nullable', 'string', 'max:64'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer'],
            'variants.*.image' => ['nullable', 'string', 'max:1000'],
            'variants.*.is_active' => ['boolean'],
        ]);
    }

    protected function syncVariants(Product $product, array $variants): void
    {
        $keepIds = [];

        foreach ($variants as $i => $v) {
            $attrs = [
                'color' => $v['color'] ?? null,
                'color_hex' => $v['color_hex'] ?? null,
                'size' => $v['size'] ?? null,
                'pack_size' => $v['pack_size'] ?? 1,
                'pack_label' => $v['pack_label'] ?? null,
                'price' => $v['price'],
                'stock' => $v['stock'],
                'image' => $v['image'] ?? null,
                'is_active' => $v['is_active'] ?? true,
            ];

            $variant = ! empty($v['id']) ? $product->variants()->find($v['id']) : null;

            if ($variant) {
                if (! empty($v['sku'])) {
                    $attrs['sku'] = $v['sku'];
                }
                $variant->update($attrs);
            } else {
                $attrs['sku'] = ! empty($v['sku']) ? $v['sku'] : $this->sku($product, $i);
                $variant = $product->variants()->create($attrs);
            }
            $keepIds[] = $variant->id;
        }

        if (empty($keepIds)) {
            // Every product needs at least one purchasable variant.
            $variant = $product->variants()->create([
                'sku' => $this->sku($product, 0),
                'price' => $product->base_price,
                'stock' => 0,
                'pack_size' => 1,
            ]);
            $keepIds[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keepIds)->delete();
        $product->update(['base_price' => $product->variants()->min('price') ?? $product->base_price]);
    }

    protected function sku(Product $product, int $i): string
    {
        do {
            $sku = strtoupper(Str::substr(Str::slug($product->name, ''), 0, 6)).'-'.$product->id.'-'.($i + 1).'-'.strtoupper(Str::random(3));
        } while (ProductVariant::where('sku', $sku)->exists());

        return $sku;
    }

    protected function slug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product-'.Str::random(6);
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
