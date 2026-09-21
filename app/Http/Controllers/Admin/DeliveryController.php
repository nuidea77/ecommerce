<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user:id,name,phone', 'courier:id,name,phone'])->withCount('items')
            ->where('status', '!=', 'cancelled');

        match ($request->query('tab', 'active')) {
            'unassigned' => $query->where('delivery_status', 'unassigned')->whereIn('status', ['confirmed', 'processing']),
            'active' => $query->whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit']),
            'failed' => $query->where('delivery_status', 'failed'),
            'done' => $query->where('delivery_status', 'delivered'),
            default => null,
        };

        if ($request->filled('courier_id')) {
            $query->where('courier_id', $request->query('courier_id'));
        }
        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->query('delivery_status'));
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('order_number', 'like', "%$q%")
                ->orWhere('shipping_name', 'like', "%$q%")
                ->orWhere('shipping_phone', 'like', "%$q%")
                ->orWhere('shipping_address', 'like', "%$q%"));
        }

        $today = now()->startOfDay();

        return response()->json([
            'deliveries' => $query->latest('updated_at')->paginate((int) $request->query('per_page', 20))->withQueryString(),
            'stats' => [
                'unassigned' => Order::where('delivery_status', 'unassigned')->whereIn('status', ['confirmed', 'processing'])->count(),
                'assigned' => Order::where('delivery_status', 'assigned')->count(),
                'in_progress' => Order::whereIn('delivery_status', ['picked_up', 'in_transit'])->count(),
                'failed' => Order::where('delivery_status', 'failed')->count(),
                'delivered_today' => Order::where('delivery_status', 'delivered')->where('delivered_at', '>=', $today)->count(),
                'delivered_total' => Order::where('delivery_status', 'delivered')->count(),
            ],
            'couriers' => User::where('role', 'courier')->withCount([
                'deliveries as active_count' => fn ($q) => $q->whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit']),
                'deliveries as delivered_today_count' => fn ($q) => $q->where('delivery_status', 'delivered')->where('delivered_at', '>=', $today),
                'deliveries as delivered_count' => fn ($q) => $q->where('delivery_status', 'delivered'),
                'deliveries as failed_count' => fn ($q) => $q->where('delivery_status', 'failed'),
            ])->get(['id', 'name', 'phone', 'is_active']),
        ]);
    }
}
