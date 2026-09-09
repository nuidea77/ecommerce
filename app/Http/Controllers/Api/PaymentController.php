<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\QPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected QPayService $qpay) {}

    /** Create (or reuse) a QPay invoice for the order. */
    public function invoice(Request $request, string $orderNumber): JsonResponse
    {
        $order = $request->user()->orders()->where('order_number', $orderNumber)->firstOrFail();

        if ($order->payment_status === 'paid') {
            return response()->json(['payment' => $order->latestPayment, 'paid' => true]);
        }

        if ($order->status === 'cancelled') {
            return response()->json(['message' => 'Захиалга цуцлагдсан байна.'], 422);
        }

        $payment = $this->qpay->createInvoice($order);

        return response()->json(['payment' => $payment, 'paid' => false, 'mock' => $this->qpay->isMock()]);
    }

    /** Poll payment state. */
    public function check(Request $request, string $orderNumber): JsonResponse
    {
        $order = $request->user()->orders()->where('order_number', $orderNumber)->firstOrFail();
        $payment = $order->latestPayment;

        if ($payment && $payment->status !== 'paid') {
            $payment = $this->qpay->check($payment);
        }

        return response()->json([
            'paid' => $order->fresh()->payment_status === 'paid',
            'payment' => $payment,
            'order' => $order->fresh(),
        ]);
    }

    /** Mock-only: simulate a successful bank payment. */
    public function simulate(Request $request, string $orderNumber): JsonResponse
    {
        abort_unless($this->qpay->isMock(), 404);

        $order = $request->user()->orders()->where('order_number', $orderNumber)->firstOrFail();
        $payment = $order->payments()->where('status', 'pending')->latest()->firstOrFail();
        $this->qpay->markPaid($payment, 'MOCK-'.strtoupper(bin2hex(random_bytes(4))), ['simulated' => true]);

        return response()->json(['paid' => true, 'order' => $order->fresh()]);
    }

    /** QPay server-to-server callback: GET /api/payments/qpay/callback?order=..&token=..&payment_id=.. */
    public function callback(Request $request): JsonResponse
    {
        $order = Order::where('order_number', $request->query('order'))->firstOrFail();

        abort_unless(hash_equals($this->qpay->callbackToken($order), (string) $request->query('token')), 403);

        $payment = $order->payments()->where('status', 'pending')->latest()->first();
        if ($payment) {
            $this->qpay->check($payment);
        }

        return response()->json(['ok' => true]);
    }
}
