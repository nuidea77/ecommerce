<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $days = in_array((int) $request->query('days'), [7, 14, 30, 90]) ? (int) $request->query('days') : 14;
        $from = now()->subDays($days - 1)->startOfDay();
        $prevFrom = $from->copy()->subDays($days);
        $today = now()->startOfDay();

        $paid = fn () => Order::where('payment_status', 'paid');
        $revenue = (float) $paid()->where('created_at', '>=', $from)->sum('total');
        $revenuePrev = (float) $paid()->whereBetween('created_at', [$prevFrom, $from])->sum('total');
        $ordersCount = Order::where('created_at', '>=', $from)->count();
        $ordersPrev = Order::whereBetween('created_at', [$prevFrom, $from])->count();
        $paidCount = $paid()->where('created_at', '>=', $from)->count();
        $customersNew = User::where('role', 'customer')->where('created_at', '>=', $from)->count();
        $customersPrev = User::where('role', 'customer')->whereBetween('created_at', [$prevFrom, $from])->count();

        $pct = fn (float $cur, float $prev) => $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : ($cur > 0 ? 100 : 0);

        $byDay = Order::where('created_at', '>=', $from)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as revenue"), DB::raw('COUNT(*) as orders'))
            ->groupBy('day')->orderBy('day')->get()->keyBy(fn ($r) => substr((string) $r->day, 0, 10));

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i);
            $row = $byDay->get($d->toDateString());
            $series[] = ['day' => $d->toDateString(), 'revenue' => (float) ($row->revenue ?? 0), 'orders' => (int) ($row->orders ?? 0)];
        }

        $paymentMethods = Order::where('created_at', '>=', $from)->where('status', '!=', 'cancelled')
            ->select('payment_method', DB::raw('COUNT(*) as orders'), DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as revenue"))
            ->groupBy('payment_method')->get();

        $categorySales = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.created_at', '>=', $from)->where('orders.status', '!=', 'cancelled')
            ->select('categories.name', DB::raw('SUM(order_items.line_total) as revenue'), DB::raw('SUM(order_items.quantity) as qty'))
            ->groupBy('categories.id', 'categories.name')->orderByDesc('revenue')->get();

        $topProducts = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $from)->where('orders.status', '!=', 'cancelled')
            ->select('order_items.product_id', 'order_items.product_name', 'order_items.image', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.line_total) as revenue'))
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.image')->orderByDesc('revenue')->take(6)->get();

        $couriers = User::where('role', 'courier')->withCount([
            'deliveries as active_count' => fn ($q) => $q->whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit']),
            'deliveries as delivered_count' => fn ($q) => $q->where('delivery_status', 'delivered')->where('delivered_at', '>=', $from),
            'deliveries as failed_count' => fn ($q) => $q->where('delivery_status', 'failed'),
        ])->get(['id', 'name', 'phone', 'is_active']);

        $backorderItems = OrderItem::where('is_backorder', true)
            ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled']))
            ->select('product_variant_id', 'product_name', 'variant_label', DB::raw('SUM(quantity) as qty'), DB::raw('COUNT(DISTINCT order_id) as orders'))
            ->groupBy('product_variant_id', 'product_name', 'variant_label')->orderByDesc('qty')->take(8)->get();

        return response()->json([
            'period' => ['days' => $days, 'from' => $from->toDateString(), 'to' => now()->toDateString()],
            'kpis' => [
                'revenue' => ['value' => $revenue, 'prev' => $revenuePrev, 'change' => $pct($revenue, $revenuePrev)],
                'orders' => ['value' => $ordersCount, 'prev' => $ordersPrev, 'change' => $pct($ordersCount, $ordersPrev)],
                'avg_order' => ['value' => $paidCount ? round($revenue / $paidCount) : 0],
                'customers_new' => ['value' => $customersNew, 'prev' => $customersPrev, 'change' => $pct($customersNew, $customersPrev)],
            ],
            'stats' => [
                'orders_today' => Order::where('created_at', '>=', $today)->count(),
                'orders_total' => Order::count(),
                'revenue_total' => (float) $paid()->sum('total'),
                'orders_pending' => Order::whereIn('status', ['pending', 'confirmed'])->count(),
                'awaiting_payment' => Order::where('status', 'awaiting_payment')->count(),
                'awaiting_payment' => Order::where('payment_status', 'unpaid')->where('payment_method', 'qpay')->where('status', '!=', 'cancelled')->count(),
                'unassigned' => Order::where('delivery_status', 'unassigned')->whereIn('status', ['confirmed', 'processing'])->count(),
                'deliveries_active' => Order::whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit'])->count(),
                'deliveries_failed' => Order::where('delivery_status', 'failed')->count(),
                'backorders' => Order::where('has_backorder', true)->whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'customers' => User::where('role', 'customer')->count(),
                'customers_verified' => User::where('role', 'customer')->where('is_verified', true)->count(),
                'couriers' => User::where('role', 'courier')->count(),
                'products' => Product::count(),
                'products_active' => Product::where('is_active', true)->count(),
                'low_stock' => ProductVariant::where('stock', '<=', 3)->where('stock', '>', 0)->count(),
                'out_of_stock' => ProductVariant::where('stock', '<=', 0)->count(),
            ],
            'series' => $series,
            'status_breakdown' => Order::select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->pluck('count', 'status'),
            'payment_methods' => $paymentMethods,
            'category_sales' => $categorySales,
            'top_products' => $topProducts,
            'couriers' => $couriers,
            'recent_orders' => Order::with('user:id,name,is_verified')->latest()->take(8)->get(),
            'attention_orders' => Order::with('user:id,name')->where(function ($q) {
                $q->whereIn('status', ['pending', 'confirmed'])->orWhere('delivery_status', 'failed')
                    ->orWhere(fn ($w) => $w->where('status', 'awaiting_payment')->where('created_at', '<', now()->subHours(6)))
                    ->orWhere(fn ($w) => $w->where('status', 'processing')->where('delivery_status', 'unassigned'));
            })->where('status', '!=', 'cancelled')->oldest()->take(6)->get(),
            'low_stock_variants' => ProductVariant::with('product:id,name,slug')->where('stock', '<=', 3)->orderBy('stock')->take(8)->get(),
            'backorder_items' => $backorderItems,
            'recent_customers' => User::where('role', 'customer')->withCount('orders')->latest()->take(5)->get(['id', 'name', 'email', 'is_verified', 'created_at']),
        ]);
    }
}
