<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\QPayService;
use App\Support\Locations;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(protected CartService $carts, protected QPayService $qpay) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()->with(['items', 'courier:id,name,phone'])
            ->latest()->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = $request->user()->orders()->where('order_number', $orderNumber)
            ->with(['items', 'histories.user:id,name,role', 'courier:id,name,phone', 'latestPayment'])
            ->firstOrFail();

        return response()->json(['order' => $order, 'qpay_mock' => $this->qpay->isMock()]);
    }

    public function store(Request $request): JsonResponse
    {
        if (config('verify.require_for_checkout') && $request->user()->needsVerification()) {
            return response()->json([
                'message' => 'Захиалга өгөхийн тулд утасны дугаараа SMS-ээр баталгаажуулна уу.',
                'verification_required' => true,
            ], 403);
        }

        $request->validate([
            'address_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
            'save_address' => ['nullable', 'boolean'],
        ]);

        // Delivery address: a saved one (address_id) or a new one validated like the address book.
        if ($request->filled('address_id')) {
            $address = $request->user()->addresses()->findOrFail($request->input('address_id'));
            $addr = $address->only('recipient_name', 'phone', 'province', 'district', 'khoroo', 'address');
        } else {
            $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);
            $addr = $request->validate(AddressController::rules(), AddressController::messages());
            if (! Locations::isValid($addr['province'], $addr['district'], $addr['khoroo'] ?? null)) {
                throw ValidationException::withMessages(['district' => 'Хот/аймаг, дүүрэг/сум, хорооны мэдээлэл таарахгүй байна.']);
            }
            if ($request->boolean('save_address')) {
                $isFirst = ! $request->user()->addresses()->exists();
                $request->user()->addresses()->create(array_merge($addr, ['label' => $request->input('label'), 'is_default' => $isFirst]));
            }
        }

        $data = [
            'shipping_name' => $addr['recipient_name'],
            'shipping_phone' => $addr['phone'],
            'shipping_city' => $addr['province'],
            'shipping_district' => $addr['district'],
            'shipping_khoroo' => $addr['khoroo'] ?? null,
            'shipping_address' => $addr['address'],
            'note' => $request->input('note'),
            'payment_method' => 'qpay',
        ];

        $cart = $this->carts->resolve($request, false);
        $payload = $cart ? $this->carts->payload($cart) : null;

        if (! $payload || $payload['items']->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Таны сагс хоосон байна.']);
        }

        $order = DB::transaction(function () use ($request, $data, $cart, $payload) {
            $order = Order::create($data + [
                'order_number' => Order::generateNumber(),
                'user_id' => $request->user()->id,
                'status' => 'awaiting_payment',
                'payment_status' => 'unpaid',
                'subtotal' => $payload['subtotal'],
                'shipping_fee' => $payload['shipping_fee'],
                'total' => $payload['total'],
                'has_backorder' => $payload['has_backorder'],
            ]);

            foreach ($payload['items'] as $item) {
                $variant = ProductVariant::lockForUpdate()->with('product')->find($item['variant_id']);
                if (! $variant) {
                    continue;
                }
                $isBackorder = $variant->stock < $item['quantity'];
                if ($isBackorder && ! $variant->product->allow_backorder) {
                    throw ValidationException::withMessages([
                        'cart' => "{$item['name']} ({$item['variant_label']}) үлдэгдэл хүрэлцэхгүй байна.",
                    ]);
                }

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $item['name'],
                    'variant_label' => $item['variant_label'],
                    'sku' => $variant->sku,
                    'image' => $item['image'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                    'is_backorder' => $isBackorder,
                ]);

                // Stock may go negative = reserved backorder quantity.
                $variant->decrement('stock', $item['quantity']);
                $variant->product->increment('sold_count', $item['quantity']);
            }

            $order->addHistory('pending', $payload['has_backorder']
                ? 'Захиалга үүслээ (урьдчилсан захиалга агуулсан)'
                : 'Захиалга үүслээ');
            $order->addHistory('awaiting_payment', 'QPay төлбөр хүлээгдэж байна');

            $cart->items()->delete();

            return $order;
        });

        $payment = $this->qpay->createInvoice($order);

        return response()->json([
            'order' => $order->load('items'),
            'payment' => $payment,
        ], 201);
    }

    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $order = $request->user()->orders()->where('order_number', $orderNumber)->firstOrFail();

        if (! in_array($order->status, Order::CANCELLABLE)) {
            throw ValidationException::withMessages(['status' => 'Энэ захиалгыг цуцлах боломжгүй.']);
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::where('id', $item->product_variant_id)->increment('stock', $item->quantity);
                }
            }
            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $order->payments()->where('status', 'pending')->update(['status' => 'cancelled']);
            $order->addHistory('cancelled', 'Хэрэглэгч захиалгаа цуцаллаа');
        });

        return response()->json(['order' => $order->fresh(['items', 'histories.user'])]);
    }
}
