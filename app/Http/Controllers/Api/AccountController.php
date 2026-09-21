<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $orders = $user->orders();

        $backorderItems = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id)->whereNotIn('status', ['delivered', 'cancelled']))
            ->where('is_backorder', true)->with('order:id,order_number,created_at,status')->latest()->take(6)->get();

        $recentProductIds = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotNull('product_id')->latest()->pluck('product_id')->unique()->take(4);

        return response()->json([
            'stats' => [
                'orders_total' => (clone $orders)->count(),
                'orders_active' => (clone $orders)->whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'unpaid' => (clone $orders)->where('status', 'awaiting_payment')->count(),
                'spent_total' => (float) (clone $orders)->where('payment_status', 'paid')->sum('total'),
                'backorders' => (clone $orders)->where('has_backorder', true)->whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'delivered' => (clone $orders)->where('status', 'delivered')->count(),
            ],
            'recent_orders' => (clone $orders)->with('items:id,order_id,image,product_name')->latest()->take(5)->get(),
            'active_deliveries' => (clone $orders)->whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit'])
                ->with('courier:id,name,phone')->latest()->take(3)->get(),
            'unpaid_orders' => (clone $orders)->where('status', 'awaiting_payment')->with('latestPayment')->latest()->take(3)->get(),
            'backorder_items' => $backorderItems,
            'reorder' => Product::active()->whereIn('id', $recentProductIds)->with(['variants', 'category'])->get(),
            'recommended' => Product::active()->where('is_featured', true)->with(['variants', 'category'])->inRandomOrder()->take(4)->get(),
        ]);
    }
}
