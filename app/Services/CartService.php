<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartService
{
    public const HEADER = 'X-Cart-Token';

    public function resolve(Request $request, bool $create = true): ?Cart
    {
        $user = $request->user();
        $token = $request->header(self::HEADER) ?: $request->cookie('cart_token');

        $cart = null;

        if ($user) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            // Merge guest cart into user's cart
            if ($token) {
                $guest = Cart::where('token', $token)->whereNull('user_id')->with('items')->first();
                if ($guest && $guest->id !== $cart->id) {
                    foreach ($guest->items as $item) {
                        $existing = $cart->items()->where('product_variant_id', $item->product_variant_id)->first();
                        if ($existing) {
                            $existing->increment('quantity', $item->quantity);
                        } else {
                            $cart->items()->create($item->only('product_variant_id', 'quantity'));
                        }
                    }
                    $guest->delete();
                }
            }
        } elseif ($token) {
            $cart = Cart::where('token', $token)->whereNull('user_id')->first();
        }

        if (! $cart && $create) {
            $cart = Cart::create(['token' => Str::random(48)]);
        }

        return $cart;
    }

    public function payload(Cart $cart): array
    {
        $cart->load(['items.variant.product.category']);

        $items = $cart->items->filter(fn ($i) => $i->variant && $i->variant->product)->values()->map(function ($item) {
            $v = $item->variant;
            $p = $v->product;

            return [
                'id' => $item->id,
                'variant_id' => $v->id,
                'product_id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'variant_label' => $v->label,
                'color' => $v->color,
                'color_hex' => $v->color_hex,
                'size' => $v->size,
                'pack_size' => $v->pack_size,
                'pack_label' => $v->pack_label,
                'price' => $v->price,
                'quantity' => $item->quantity,
                'stock' => $v->stock,
                'in_stock' => $v->stock > 0,
                'is_backorder' => $v->stock < $item->quantity,
                'allow_backorder' => $p->allow_backorder,
                'backorder_days' => $p->backorder_days,
                'image' => $v->image ?: $p->thumbnail,
                'line_total' => round($v->price * $item->quantity, 2),
            ];
        });

        $subtotal = round($items->sum('line_total'), 2);
        $shipping = $subtotal >= config('shop.free_shipping_threshold') || $subtotal == 0 ? 0 : config('shop.shipping_fee');

        return [
            'token' => $cart->token,
            'items' => $items,
            'count' => (int) $items->sum('quantity'),
            'subtotal' => $subtotal,
            'shipping_fee' => $shipping,
            'total' => round($subtotal + $shipping, 2),
            'has_backorder' => $items->contains('is_backorder', true),
            'free_shipping_threshold' => config('shop.free_shipping_threshold'),
        ];
    }
}
