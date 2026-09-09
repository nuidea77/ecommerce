<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user:id,name,email,phone', 'courier:id,name,phone'])->withCount('items');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('order_number', 'like', "%$q%")
                ->orWhere('shipping_name', 'like', "%$q%")
                ->orWhere('shipping_phone', 'like', "%$q%"));
        }
        foreach (['status', 'payment_status', 'delivery_status', 'payment_method'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->query($f));
            }
        }
        if ($request->boolean('backorder')) {
            $query->where('has_backorder', true);
        }

        return response()->json($query->latest()->paginate((int) $request->query('per_page', 15))->withQueryString());
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'order' => $order->load(['user', 'courier', 'items', 'histories.user:id,name,role', 'payments']),
            'couriers' => User::where('role', 'courier')->where('is_active', true)->withCount([
                'deliveries as active_deliveries_count' => fn ($q) => $q->whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit']),
            ])->get(['id', 'name', 'phone']),
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($order, $data) {
            $updates = ['status' => $data['status']];
            if ($data['status'] === 'shipped') {
                $updates['shipped_at'] = now();
            }
            if ($data['status'] === 'delivered') {
                $updates['delivered_at'] = now();
                $updates['delivery_status'] = 'delivered';
            }
            if ($data['status'] === 'cancelled' && $order->status !== 'cancelled') {
                $updates['cancelled_at'] = now();
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::where('id', $item->product_variant_id)->increment('stock', $item->quantity);
                    }
                }
                $order->payments()->where('status', 'pending')->update(['status' => 'cancelled']);
            }
            $order->update($updates);
            $order->addHistory($data['status'], $data['comment'] ?? null);
        });

        return $this->show($order->fresh());
    }

    public function updatePayment(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(['unpaid', 'paid', 'refunded', 'failed'])],
        ]);

        $order->update([
            'payment_status' => $data['payment_status'],
            'paid_at' => $data['payment_status'] === 'paid' ? now() : $order->paid_at,
        ]);
        if ($data['payment_status'] === 'paid') {
            $order->payments()->where('status', 'pending')->update(['status' => 'paid', 'paid_at' => now()]);
        }
        $order->addHistory($data['payment_status'], 'Админ төлбөрийн төлөв өөрчиллөө', 'payment');

        return $this->show($order->fresh());
    }

    public function assignCourier(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'courier_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'courier')],
        ]);

        $order->update([
            'courier_id' => $data['courier_id'],
            'delivery_status' => $data['courier_id'] ? 'assigned' : 'unassigned',
            'status' => $data['courier_id'] && in_array($order->status, ['pending', 'confirmed']) ? 'processing' : $order->status,
        ]);

        $courier = $data['courier_id'] ? User::find($data['courier_id']) : null;
        $order->addHistory(
            $data['courier_id'] ? 'assigned' : 'unassigned',
            $courier ? "Хүргэлтийн ажилтан {$courier->name} томилогдлоо" : 'Хүргэлтийн ажилтан цуцлагдлаа',
            'delivery'
        );

        return $this->show($order->fresh());
    }
}
