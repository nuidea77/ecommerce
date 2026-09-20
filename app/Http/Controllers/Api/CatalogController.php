<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
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

        $brands = Product::active()->whereNotNull('brand')->select('brand')->distinct()->orderBy('brand')->pluck('brand');

        return response()->json(compact('categories', 'featured', 'newest', 'bestsellers', 'sale', 'brands'));
    }

    public function categories(): JsonResponse
    {
        return response()->json(
            Category::where('is_active', true)->orderBy('sort_order')
                ->withCount(['products' => fn ($q) => $q->active()])->get()
        );
    }

    public function products(Request $request): JsonResponse
    {
        $query = Product::active()->with(['variants', 'category']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category)->orWhere('id', $category));
        }

        if ($brand = $request->query('brand')) {
            $query->where('brand', $brand);
        }

        if ($color = $request->query('color')) {
            $query->whereHas('variants', fn ($q) => $q->where('color', $color));
        }

        if ($size = $request->query('size')) {
            $query->whereHas('variants', fn ($q) => $q->where('size', $size));
        }

        if ($request->filled('min_price')) {
            $query->whereHas('variants', fn ($q) => $q->where('price', '>=', (float) $request->query('min_price')));
        }
        if ($request->filled('max_price')) {
            $query->whereHas('variants', fn ($q) => $q->where('price', '<=', (float) $request->query('max_price')));
        }

        if ($request->boolean('in_stock')) {
            $query->whereHas('variants', fn ($q) => $q->where('stock', '>', 0));
        }

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            'newest' => $query->latest(),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderByDesc('sold_count'),
        };

        $products = $query->paginate((int) $request->query('per_page', 12))->withQueryString();

        return response()->json($products);
    }

    public function filters(): JsonResponse
    {
        return response()->json([
            'colors' => ProductVariant::query()->whereNotNull('color')->where('color', '!=', '')
                ->toBase()->select('color', 'color_hex')->distinct()->orderBy('color')->get()->values(),
            'sizes' => ProductVariant::query()->whereNotNull('size')->where('size', '!=', '')
                ->toBase()->distinct()->orderBy('size')->pluck('size'),
            'brands' => Product::active()->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand'),
            'price' => [
                'min' => (float) (ProductVariant::min('price') ?? 0),
                'max' => (float) (ProductVariant::max('price') ?? 0),
            ],
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
