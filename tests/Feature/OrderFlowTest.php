<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function as(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user);
    }

    protected function makeProduct(int $stock = 5): Product
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Hair dryer', 'slug' => 'hair-dryer', 'base_price' => 1000, 'allow_backorder' => true,
        ]);
        $product->variants()->create(['sku' => 'HD-1', 'color' => 'Black', 'price' => 1000, 'stock' => $stock]);

        return $product;
    }

    public function test_guest_can_add_to_cart_and_variant_options_are_returned(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants->first();

        $this->getJson('/api/products/hair-dryer')->assertOk()->assertJsonPath('product.variants.0.label', 'Black');

        $res = $this->postJson('/api/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertOk();
        $this->assertSame(2, $res->json('count'));
        $this->assertNotEmpty($res->json('token'));
    }

    public function test_customer_can_order_out_of_stock_item_as_backorder_and_pay_with_mock_qpay(): void
    {
        config(['qpay.mock' => true]);
        $product = $this->makeProduct(stock: 0);
        $variant = $product->variants->first();
        $user = User::create(['phone' => '99000001', 'name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer', 'is_verified' => true]);

        $this->as($user)->postJson('/api/cart/items', ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertOk()->assertJsonPath('has_backorder', true);

        $res = $this->as($user)->postJson('/api/orders', [
            'recipient_name' => 'Сарнай', 'phone' => '99001122', 'province' => 'Улаанбаатар', 'district' => 'Хан-Уул', 'khoroo' => '15',
            'address' => 'Мишээл экспо, Beauty Studio', 'save_address' => true,
        ])->assertCreated();
        $this->assertSame(1, $user->addresses()->count());
        $this->assertSame('qpay', $res->json('order.payment_method'));

        $number = $res->json('order.order_number');
        $this->assertTrue($res->json('order.has_backorder'));
        $this->assertNotEmpty($res->json('payment.qr_text'));
        $this->assertSame(-1, $variant->fresh()->stock);

        $this->as($user)->getJson("/api/orders/{$number}/payment/check")->assertOk()->assertJsonPath('paid', false);
        $this->as($user)->postJson("/api/orders/{$number}/payment/simulate")->assertOk()->assertJsonPath('paid', true);
        $this->as($user)->getJson("/api/orders/{$number}")->assertOk()
            ->assertJsonPath('order.payment_status', 'paid')->assertJsonPath('order.status', 'confirmed');
    }

    public function test_admin_assigns_courier_and_courier_completes_delivery(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants->first();
        $admin = User::create(['phone' => '99000002', 'name' => 'A', 'email' => 'a@x.mn', 'password' => 'password', 'role' => 'admin']);
        $courier = User::create(['phone' => '99000003', 'name' => 'K', 'email' => 'k@x.mn', 'password' => 'password', 'role' => 'courier']);
        $user = User::create(['phone' => '99000004', 'name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer', 'is_verified' => true]);

        $this->as($user)->postJson('/api/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $address = $user->addresses()->create(['recipient_name' => 'Болд', 'phone' => '99001122', 'province' => 'Дархан-Уул', 'district' => 'Дархан', 'khoroo' => '5', 'address' => 'Glam Salon', 'is_default' => true]);
        $orderRes = $this->as($user)->postJson('/api/orders', ['address_id' => $address->id])->assertCreated();
        $orderId = $orderRes->json('order.id');
        $this->assertSame('Дархан-Уул', $orderRes->json('order.shipping_city'));
        // an invalid province/district pair is rejected
        $this->as($user)->postJson('/api/orders', ['recipient_name' => 'Болд', 'phone' => '99001122', 'province' => 'Улаанбаатар', 'district' => 'Дархан', 'address' => 'x y z 12'])->assertStatus(422);

        $this->as($user)->getJson('/api/admin/orders')->assertForbidden();
        $this->as($admin)->patchJson("/api/admin/orders/{$orderId}/courier", ['courier_id' => $courier->id])
            ->assertOk()->assertJsonPath('order.delivery_status', 'assigned')->assertJsonPath('order.status', 'processing');

        $this->as($courier)->getJson('/api/courier/deliveries')->assertOk()->assertJsonPath('stats.assigned', 1);
        $this->as($courier)->patchJson("/api/courier/deliveries/{$orderId}/status", ['delivery_status' => 'picked_up'])->assertOk()->assertJsonPath('status', 'shipped');
        $this->as($courier)->patchJson("/api/courier/deliveries/{$orderId}/status", ['delivery_status' => 'delivered'])
            ->assertOk()->assertJsonPath('status', 'delivered')->assertJsonPath('payment_status', 'unpaid');
    }
}
