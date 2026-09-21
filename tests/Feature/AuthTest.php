<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('Referer', 'http://localhost');
    }

    public function test_registers_with_phone_and_normalises_country_code(): void
    {
        $res = $this->postJson('/api/auth/register', [
            'name' => 'Сарнай', 'phone' => '+976 9900-1122', 'password' => 'salon2024', 'password_confirmation' => 'salon2024',
        ])->assertCreated()->assertJsonPath('verification_required', true);

        $this->assertSame('99001122', $res->json('user.phone'));
        $this->assertNull($res->json('user.email'));
        $this->assertFalse($res->json('user.is_verified'));
        $this->assertAuthenticated();
    }

    public function test_registration_rejects_bad_phone_name_and_weak_password(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'A1', 'phone' => '12345678', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'phone', 'password']);

        $this->postJson('/api/auth/register', ['name' => 'Болд', 'phone' => '9900112', 'password' => 'abc12345', 'password_confirmation' => 'abc12345'])
            ->assertStatus(422)->assertJsonValidationErrors(['phone']);

        $this->postJson('/api/auth/register', ['name' => 'Болд', 'phone' => '99001122', 'password' => '12345678', 'password_confirmation' => '12345678'])
            ->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->postJson('/api/auth/register', ['name' => 'Болд', 'phone' => '99001122', 'password' => 'abc12345', 'password_confirmation' => 'other'])
            ->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_phone_must_be_unique(): void
    {
        User::create(['name' => 'A', 'phone' => '99001122', 'password' => 'password']);
        $this->postJson('/api/auth/register', ['name' => 'Болд', 'phone' => '99001122', 'password' => 'abc12345', 'password_confirmation' => 'abc12345'])
            ->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }

    public function test_logs_in_with_phone(): void
    {
        User::create(['name' => 'A', 'phone' => '99001122', 'password' => 'abc12345']);

        $this->postJson('/api/auth/login', ['phone' => '99001122', 'password' => 'wrong'])->assertStatus(422);
        $this->postJson('/api/auth/login', ['phone' => '976 99001122', 'password' => 'abc12345'])->assertOk()->assertJsonPath('user.phone', '99001122');
    }

    public function test_verification_uses_the_registered_phone(): void
    {
        config(['verify.mock' => true, 'verify.api_key' => null]);
        $user = User::create(['name' => 'A', 'phone' => '88001122', 'password' => 'abc12345']);

        $this->actingAs($user)->postJson('/api/verify/start', ['phone' => '99999999'])
            ->assertOk()->assertJsonPath('session.phone', '88001122');
    }
}
