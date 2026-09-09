<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $courier = $request->user();
        $query = $courier->deliveries()->with(['user:id,name,phone'])->withCount('items');

        $tab = $request->query('tab', 'active');
        if ($tab === 'active') {
            $query->whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit']);
        } elseif ($tab === 'done') {
            $query->whereIn('delivery_status', ['delivered', 'failed']);
        }

        return response()->json([
            'deliveries' => $query->latest('updated_at')->paginate(20),
            'stats' => [
                'assigned' => $courier->deliveries()->where('delivery_status', 'assigned')->count(),
                'in_progress' => $courier->deliveries()->whereIn('delivery_status', ['picked_up', 'in_transit'])->count(),
                'delivered_today' => $courier->deliveries()->where('delivery_status', 'delivered')->where('delivered_at', '>=', now()->startOfDay())->count(),
                'delivered_total' => $courier->deliveries()->where('delivery_status', 'delivered')->count(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->courier_id === $request->user()->id, 403);

        return response()->json($order->load(['items', 'user:id,name,phone', 'histories.user:id,name,role']));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->courier_id === $request->user()->id, 403);

        $data = $request->validate([
            'delivery_status' => ['required', Rule::in(['picked_up', 'in_transit', 'delivered', 'failed'])],
            'courier_note' => ['nullable', 'string', 'max:500'],
            'cash_collected' => ['boolean'],
        ]);

        $updates = ['delivery_status' => $data['delivery_status']];
        if (! empty($data['courier_note'])) {
            $updates['courier_note'] = $data['courier_note'];
        }

        if ($data['delivery_status'] === 'picked_up' || $data['delivery_status'] === 'in_transit') {
            $updates['status'] = 'shipped';
            $updates['shipped_at'] = $order->shipped_at ?? now();
        }
        if ($data['delivery_status'] === 'delivered') {
            $updates['status'] = 'delivered';
            $updates['delivered_at'] = now();
            if ($order->payment_method === 'cash' && $request->boolean('cash_collected')) {
                $updates['payment_status'] = 'paid';
                $updates['paid_at'] = now();
            }
        }

        $order->update($updates);
        $order->addHistory($data['delivery_status'], $data['courier_note'] ?? null, 'delivery');
        if ($data['delivery_status'] === 'delivered' && ($updates['payment_status'] ?? null) === 'paid') {
            $order->addHistory('paid', 'Бэлэн мөнгөөр төлбөр хүлээн авлаа', 'payment');
        }

        return response()->json($order->fresh(['items', 'user:id,name,phone', 'histories.user:id,name,role']));
    }
}
