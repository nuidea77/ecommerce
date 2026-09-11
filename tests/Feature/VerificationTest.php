<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Sanctum attaches the session only to requests coming from the SPA origin.
        $this->withHeader('Referer', 'http://localhost');
    }

    public function test_unverified_customer_cannot_checkout_when_required(): void
    {
        config(['verify.require_for_checkout' => true]);
        $user = User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);

        $this->actingAs($user)->postJson('/api/orders', ['shipping_name' => 'C'])
            ->assertForbidden()->assertJsonPath('verification_required', true);
    }

    public function test_mock_verification_flow_marks_user_verified(): void
    {
        config(['verify.mock' => true]);
        $user = User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);

        $this->actingAs($user)->getJson('/api/verify/status')->assertOk()->assertJsonPath('verified', false)->assertJsonPath('mock', true);

        $start = $this->actingAs($user)->postJson('/api/verify/start')->assertOk();
        $this->assertStringContainsString('/verify/mock?state=', $start->json('url'));
        parse_str(parse_url($start->json('url'), PHP_URL_QUERY), $q);

        // wrong state is rejected
        $this->actingAs($user)->postJson('/api/verify/mock/complete', [
            'state' => 'bogus', 'register_number' => 'УБ95010112', 'last_name' => 'Бат', 'first_name' => 'Болд',
        ])->assertStatus(422);

        // restart (state was consumed) and complete with a valid register number
        $start = $this->actingAs($user)->postJson('/api/verify/start')->assertOk();
        parse_str(parse_url($start->json('url'), PHP_URL_QUERY), $q);
        $this->actingAs($user)->postJson('/api/verify/mock/complete', [
            'state' => $q['state'], 'register_number' => 'уб95010112', 'last_name' => 'Бат', 'first_name' => 'Болд',
        ])->assertOk()->assertJsonPath('verified', true)->assertJsonPath('user.is_verified', true);

        $user->refresh();
        $this->assertSame('УБ95010112', $user->register_number);
        $this->assertNotNull($user->verified_at);

        $status = $this->actingAs($user)->getJson('/api/verify/status')->assertOk()->assertJsonPath('verified', true);
        $this->assertSame('УБ******12', $status->json('register_number'));
    }

    public function test_register_number_cannot_be_reused_by_another_account(): void
    {
        config(['verify.mock' => true]);
        User::create(['name' => 'A', 'email' => 'a@x.mn', 'password' => 'password', 'role' => 'customer', 'is_verified' => true, 'register_number' => 'УБ95010112']);
        $user = User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);

        $start = $this->actingAs($user)->postJson('/api/verify/start')->assertOk();
        parse_str(parse_url($start->json('url'), PHP_URL_QUERY), $q);
        $this->actingAs($user)->postJson('/api/verify/mock/complete', [
            'state' => $q['state'], 'register_number' => 'УБ95010112', 'last_name' => 'Бат', 'first_name' => 'Болд',
        ])->assertStatus(422);
        $this->assertFalse($user->fresh()->is_verified);
    }

    public function test_account_dashboard_responds(): void
    {
        $user = User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);
        $this->actingAs($user)->getJson('/api/account/dashboard')->assertOk()->assertJsonPath('stats.orders_total', 0);
    }

    public function test_admin_dashboard_responds_with_period(): void
    {
        User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);
        $admin = User::create(['name' => 'A', 'email' => 'a@x.mn', 'password' => 'password', 'role' => 'admin']);
        $this->actingAs($admin)->getJson('/api/admin/dashboard?days=30')->assertOk()
            ->assertJsonPath('period.days', 30)->assertJsonCount(30, 'series')->assertJsonPath('stats.customers', 1);
    }
}
