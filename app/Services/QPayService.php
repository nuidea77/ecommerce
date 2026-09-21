<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * QPay v2 merchant API integration (https://developer.qpay.mn).
 * Falls back to a local mock when QPAY_MOCK=true so the checkout can be exercised
 * without merchant credentials.
 */
class QPayService
{
    public function isMock(): bool
    {
        return (bool) config('qpay.mock') || ! config('qpay.username');
    }

    protected function token(): string
    {
        return Cache::remember('qpay.access_token', now()->addMinutes(50), function () {
            $response = Http::withBasicAuth(config('qpay.username'), config('qpay.password'))
                ->acceptJson()
                ->post(config('qpay.base_url').'/auth/token');

            if (! $response->successful()) {
                Log::error('QPay auth failed', ['body' => $response->body()]);
                throw new RuntimeException('QPay authentication failed');
            }

            return $response->json('access_token');
        });
    }

    public function createInvoice(Order $order): Payment
    {
        $existing = $order->payments()
            ->where('status', 'pending')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $senderInvoiceNo = $order->order_number.'-'.Str::upper(Str::random(4));
        $expiresAt = now()->addMinutes(config('qpay.invoice_ttl_minutes'));

        if ($this->isMock()) {
            $invoiceId = (string) Str::uuid();
            $qrText = 'QPAY-MOCK|'.$invoiceId.'|'.number_format($order->total, 0, '', '').'|'.$senderInvoiceNo;

            return $order->payments()->create([
                'provider' => 'qpay',
                'invoice_id' => $invoiceId,
                'sender_invoice_no' => $senderInvoiceNo,
                'amount' => $order->total,
                'status' => 'pending',
                'qr_text' => $qrText,
                'qr_image' => null,
                'short_url' => null,
                'urls' => $this->mockBankUrls($qrText),
                'raw_response' => ['mock' => true],
                'expires_at' => $expiresAt,
            ]);
        }

        $payload = [
            'invoice_code' => config('qpay.invoice_code'),
            'sender_invoice_no' => $senderInvoiceNo,
            'invoice_receiver_code' => (string) $order->user_id,
            'invoice_description' => config('shop.name').' захиалга '.$order->order_number,
            'sender_branch_code' => 'WEB',
            'amount' => (int) round($order->total),
            'callback_url' => rtrim(config('qpay.callback_url') ?: url('/api/payments/qpay/callback'), '/')
                .'?order='.$order->order_number.'&token='.$this->callbackToken($order),
        ];

        $response = Http::withToken($this->token())->acceptJson()
            ->post(config('qpay.base_url').'/invoice', $payload);

        if (! $response->successful()) {
            Log::error('QPay invoice failed', ['body' => $response->body()]);
            throw new RuntimeException('QPay нэхэмжлэх үүсгэж чадсангүй');
        }

        $data = $response->json();

        return $order->payments()->create([
            'provider' => 'qpay',
            'invoice_id' => $data['invoice_id'] ?? null,
            'sender_invoice_no' => $senderInvoiceNo,
            'amount' => $order->total,
            'status' => 'pending',
            'qr_text' => $data['qr_text'] ?? null,
            'qr_image' => $data['qr_image'] ?? null,
            'short_url' => $data['qPay_shortUrl'] ?? null,
            'urls' => $data['urls'] ?? [],
            'raw_response' => $data,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Check the invoice with QPay; marks the payment + order paid when settled.
     */
    public function check(Payment $payment): Payment
    {
        if ($payment->status === 'paid') {
            return $payment;
        }

        if ($this->isMock()) {
            return $payment;
        }

        $response = Http::withToken($this->token())->acceptJson()
            ->post(config('qpay.base_url').'/payment/check', [
                'object_type' => 'INVOICE',
                'object_id' => $payment->invoice_id,
                'offset' => ['page_number' => 1, 'page_limit' => 100],
            ]);

        if (! $response->successful()) {
            Log::warning('QPay check failed', ['body' => $response->body()]);

            return $payment;
        }

        $data = $response->json();
        $paidRow = collect($data['rows'] ?? [])->firstWhere('payment_status', 'PAID');

        if ($paidRow && (float) ($data['paid_amount'] ?? 0) >= $payment->amount) {
            $this->markPaid($payment, $paidRow['payment_id'] ?? null, $data);
        }

        return $payment->refresh();
    }

    public function markPaid(Payment $payment, ?string $paymentId = null, array $raw = []): void
    {
        if ($payment->status === 'paid') {
            return;
        }

        $payment->update([
            'status' => 'paid',
            'payment_id' => $paymentId,
            'paid_at' => now(),
            'raw_response' => array_merge($payment->raw_response ?? [], ['check' => $raw]),
        ]);

        $order = $payment->order;
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'status' => in_array($order->status, ['pending', 'awaiting_payment']) ? 'confirmed' : $order->status,
        ]);
        $order->addHistory('paid', 'QPay төлбөр амжилттай төлөгдлөө', 'payment', null);
        if ($order->wasChanged('status')) {
            $order->addHistory('confirmed', 'Төлбөр баталгаажсан тул захиалга баталгаажлаа', 'status', null);
        }
    }

    public function callbackToken(Order $order): string
    {
        return hash_hmac('sha256', $order->order_number, config('app.key'));
    }

    protected function mockBankUrls(string $qrText): array
    {
        $banks = [
            ['name' => 'qPay wallet', 'description' => 'qPay хэтэвч', 'logo' => 'https://s3.qpay.mn/p/e9bbdc69-3544-4c2f-aff0-4c292bc094f6/launcher-icon-ios.jpg', 'link' => 'qpaywallet://q?qPay_QRcode='.$qrText],
            ['name' => 'Khan bank', 'description' => 'Хаан банк', 'logo' => 'https://qpay.mn/q/logo/khanbank.png', 'link' => 'khanbank://q?qPay_QRcode='.$qrText],
            ['name' => 'State bank', 'description' => 'Төрийн банк', 'logo' => 'https://qpay.mn/q/logo/statebank.png', 'link' => 'statebank://q?qPay_QRcode='.$qrText],
            ['name' => 'Xac bank', 'description' => 'Хас банк', 'logo' => 'https://qpay.mn/q/logo/xacbank.png', 'link' => 'xacbank://q?qPay_QRcode='.$qrText],
            ['name' => 'Trade and Development bank', 'description' => 'ХХБ', 'logo' => 'https://qpay.mn/q/logo/tdbbank.png', 'link' => 'tdbbank://q?qPay_QRcode='.$qrText],
            ['name' => 'Most money', 'description' => 'МОСТ мони', 'logo' => 'https://qpay.mn/q/logo/most.png', 'link' => 'most://q?qPay_QRcode='.$qrText],
            ['name' => 'Golomt bank', 'description' => 'Голомт банк', 'logo' => 'https://qpay.mn/q/logo/golomtbank.png', 'link' => 'golomtbank://q?qPay_QRcode='.$qrText],
            ['name' => 'M bank', 'description' => 'М банк', 'logo' => 'https://qpay.mn/q/logo/mbank.png', 'link' => 'mbank://q?qPay_QRcode='.$qrText],
        ];

        return $banks;
    }
}
