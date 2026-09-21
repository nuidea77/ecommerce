<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::exists()) {
            return;
        }

        $customers = User::where('role', 'customer')->get();
        $couriers = User::where('role', 'courier')->get();
        $variants = ProductVariant::with('product')->get();

        $scenarios = [
            ['status' => 'delivered', 'payment' => 'paid', 'method' => 'qpay', 'delivery' => 'delivered', 'days' => 12],
            ['status' => 'delivered', 'payment' => 'paid', 'method' => 'qpay', 'delivery' => 'delivered', 'days' => 9],
            ['status' => 'shipped', 'payment' => 'paid', 'method' => 'qpay', 'delivery' => 'in_transit', 'days' => 2],
            ['status' => 'processing', 'payment' => 'paid', 'method' => 'qpay', 'delivery' => 'assigned', 'days' => 1],
            ['status' => 'confirmed', 'payment' => 'paid', 'method' => 'qpay', 'delivery' => 'unassigned', 'days' => 1],
            ['status' => 'awaiting_payment', 'payment' => 'unpaid', 'method' => 'qpay', 'delivery' => 'unassigned', 'days' => 0],
            ['status' => 'awaiting_payment', 'payment' => 'unpaid', 'method' => 'qpay', 'delivery' => 'unassigned', 'days' => 1],
            ['status' => 'cancelled', 'payment' => 'unpaid', 'method' => 'qpay', 'delivery' => 'unassigned', 'days' => 5],
            ['status' => 'delivered', 'payment' => 'paid', 'method' => 'qpay', 'delivery' => 'delivered', 'days' => 20],
            ['status' => 'processing', 'payment' => 'paid', 'method' => 'qpay', 'delivery' => 'picked_up', 'days' => 1],
        ];

        foreach ($scenarios as $i => $s) {
            $customer = $customers[$i % $customers->count()];
            $addr = $customer->addresses()->where('is_default', true)->first() ?? $customer->addresses()->first();
            $picked = $variants->random(rand(1, 3));
            $createdAt = now()->subDays($s['days'])->subHours(rand(1, 8));

            $items = [];
            $subtotal = 0;
            $hasBackorder = false;
            foreach ($picked as $v) {
                $qty = rand(1, 2);
                $isBack = $v->stock <= 0;
                $hasBackorder = $hasBackorder || $isBack;
                $line = $v->price * $qty;
                $subtotal += $line;
                $items[] = [
                    'product_id' => $v->product_id,
                    'product_variant_id' => $v->id,
                    'product_name' => $v->product->name,
                    'variant_label' => $v->label,
                    'sku' => $v->sku,
                    'image' => $v->product->thumbnail,
                    'price' => $v->price,
                    'quantity' => $qty,
                    'line_total' => $line,
                    'is_backorder' => $isBack,
                ];
            }

            $shipping = $subtotal >= config('shop.free_shipping_threshold') ? 0 : config('shop.shipping_fee');
            $courier = in_array($s['delivery'], ['unassigned']) ? null : $couriers[$i % $couriers->count()];

            $order = Order::create([
                'order_number' => Order::generateNumber(),
                'user_id' => $customer->id,
                'courier_id' => $courier?->id,
                'status' => $s['status'],
                'payment_method' => $s['method'],
                'payment_status' => $s['payment'],
                'delivery_status' => $s['delivery'],
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'total' => $subtotal + $shipping,
                'shipping_name' => $customer->name,
                'shipping_phone' => $customer->phone,
                'shipping_city' => $addr?->province ?? 'Улаанбаатар',
                'shipping_district' => $addr?->district ?? 'Хан-Уул',
                'shipping_khoroo' => $addr?->khoroo,
                'shipping_address' => $addr?->address ?? 'Мишээл экспо, Beauty Studio',
                'note' => $i % 3 === 0 ? 'Ажлын цагаар хүргэнэ үү' : null,
                'has_backorder' => $hasBackorder,
                'paid_at' => $s['payment'] === 'paid' ? $createdAt->copy()->addMinutes(10) : null,
                'shipped_at' => in_array($s['status'], ['shipped', 'delivered']) ? $createdAt->copy()->addDay() : null,
                'delivered_at' => $s['status'] === 'delivered' ? $createdAt->copy()->addDays(2) : null,
                'cancelled_at' => $s['status'] === 'cancelled' ? $createdAt->copy()->addHours(3) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $order->items()->createMany($items);
            $order->histories()->create(['status' => 'pending', 'comment' => 'Захиалга үүслээ', 'user_id' => $customer->id, 'created_at' => $createdAt]);
            if ($s['payment'] === 'paid') {
                $order->histories()->create(['type' => 'payment', 'status' => 'paid', 'comment' => 'QPay төлбөр амжилттай', 'created_at' => $createdAt->copy()->addMinutes(10)]);
                $order->payments()->create([
                    'provider' => $s['method'], 'invoice_id' => (string) Str::uuid(), 'amount' => $order->total,
                    'status' => 'paid', 'paid_at' => $order->paid_at, 'sender_invoice_no' => $order->order_number,
                ]);
            }
            if ($courier) {
                $order->histories()->create(['type' => 'delivery', 'status' => 'assigned', 'comment' => "Хүргэлтийн ажилтан {$courier->name} томилогдлоо", 'created_at' => $createdAt->copy()->addHours(2)]);
            }
            if ($s['status'] !== 'pending') {
                $order->histories()->create(['status' => $s['status'], 'created_at' => $createdAt->copy()->addDays(1)]);
            }
        }
    }
}
