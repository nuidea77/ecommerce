<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $revenueByDay = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as orders'))
            ->groupBy('day')->orderBy('day')->get();

        return response()->json([
            'stats' => [
                'orders_today' => Order::where('created_at', '>=', $today)->count(),
                'orders_total' => Order::count(),
                'orders_pending' => Order::whereIn('status', ['pending', 'confirmed'])->count(),
                'revenue_month' => (float) Order::where('payment_status', 'paid')->where('created_at', '>=', $monthStart)->sum('total'),
                'revenue_total' => (float) Order::where('payment_status', 'paid')->sum('total'),
                'customers' => User::where('role', 'customer')->count(),
                'couriers' => User::where('role', 'courier')->count(),
                'products' => Product::count(),
                'low_stock' => ProductVariant::where('stock', '<=', 3)->count(),
                'backorders' => Order::where('has_backorder', true)->whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'deliveries_active' => Order::whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit'])->count(),
            ],
            'revenue_by_day' => $revenueByDay,
            'status_breakdown' => Order::select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->pluck('count', 'status'),
            'recent_orders' => Order::with('user:id,name')->latest()->take(8)->get(),
            'low_stock_variants' => ProductVariant::with('product:id,name,slug')->where('stock', '<=', 3)->orderBy('stock')->take(8)->get(),
            'top_products' => Product::orderByDesc('sold_count')->take(5)->get(['id', 'name', 'slug', 'sold_count', 'images', 'base_price']),
        ]);
    }
}
