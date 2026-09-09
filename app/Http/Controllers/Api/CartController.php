<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(protected CartService $carts) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);

        return response()->json($this->carts->payload($cart));
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $variant = ProductVariant::with('product')->findOrFail($data['variant_id']);

        if (! $variant->is_active || ! $variant->product->is_active) {
            throw ValidationException::withMessages(['variant_id' => 'Энэ бүтээгдэхүүн худалдаанд байхгүй байна.']);
        }

        $cart = $this->carts->resolve($request);
        $item = $cart->items()->where('product_variant_id', $variant->id)->first();
        $newQty = ($item?->quantity ?? 0) + $data['quantity'];

        if ($newQty > $variant->stock && ! $variant->product->allow_backorder) {
            throw ValidationException::withMessages(['quantity' => "Үлдэгдэл хүрэлцэхгүй байна. Боломжит: {$variant->stock}"]);
        }

        if ($item) {
            $item->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => $newQty]);
        }

        return response()->json($this->carts->payload($cart->fresh()));
    }

    public function update(Request $request, int $itemId): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:999']]);
        $cart = $this->carts->resolve($request);
        $item = $cart->items()->with('variant.product')->findOrFail($itemId);

        if ($data['quantity'] === 0) {
            $item->delete();
        } else {
            if ($data['quantity'] > $item->variant->stock && ! $item->variant->product->allow_backorder) {
                throw ValidationException::withMessages(['quantity' => "Үлдэгдэл хүрэлцэхгүй байна. Боломжит: {$item->variant->stock}"]);
            }
            $item->update(['quantity' => $data['quantity']]);
        }

        return response()->json($this->carts->payload($cart->fresh()));
    }

    public function remove(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $cart->items()->where('id', $itemId)->delete();

        return response()->json($this->carts->payload($cart->fresh()));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $cart->items()->delete();

        return response()->json($this->carts->payload($cart->fresh()));
    }
}
