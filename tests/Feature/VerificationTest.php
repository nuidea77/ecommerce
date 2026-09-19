<?php

namespace Tests\Feature;

use App\Models\PhoneVerification;
use App\Models\User;
use App\Services\VerifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['verify.mock' => false, 'verify.api_key' => 'test-key', 'verify.base_url' => 'https://api.verify.mn', 'verify.callback_url' => null]);
        $this->withHeader('Referer', 'http://localhost'); // Sanctum attaches the session for SPA-origin requests
    }

    protected function sessionResponse(string $status, array $extra = []): array
    {
        return ['sessionId' => 'sess-1', 'sessionStatus' => $status, 'callbackStatus' => 'PENDING', 'verifiedAt' => null, 'expiresAt' => now()->addSeconds(300)->toIso8601String()] + $extra;
    }

    protected function fakeCreate(): void
    {
        Http::fake(['https://api.verify.mn/sessions' => function (Request $r) {
            return Http::response(['sessionId' => 'sess-1', 'phone' => $r['phone'], 'shortcode' => '144773', 'text' => $r['text'],
                'smsUri' => 'sms:144773?body='.$r['text'], 'displayInstruction' => "{$r['phone']} дугаараас 144773 руу {$r['text']} гэж илгээнэ үү.",
                'expiresAt' => now()->addSeconds(300)->toIso8601String()], 201);
        }]);
    }

    public function test_verify_phone_returns_true_when_session_becomes_verified(): void
    {
        Sleep::fake();
        $calls = 0;
        Http::fake([
            'https://api.verify.mn/sessions' => Http::response(['sessionId' => 'sess-1', 'phone' => '99001122', 'shortcode' => '144773', 'text' => '123456', 'smsUri' => 'sms:144773?body=123456', 'displayInstruction' => 'x', 'expiresAt' => now()->addSeconds(300)->toIso8601String()], 201),
            'https://api.verify.mn/sessions/sess-1' => function () use (&$calls) {
                return Http::response($this->sessionResponse(++$calls < 3 ? 'PENDING' : 'VERIFIED', ['verifiedAt' => now()->toIso8601String()]));
            },
        ]);
        $user = User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);

        $seen = null;
        $result = app(VerifyService::class)->verifyPhone('99001122', $user, function (PhoneVerification $s) use (&$seen) {
            $seen = $s;
        });

        $this->assertTrue($result);
        $this->assertSame(3, $calls);
        Sleep::assertSleptTimes(2);
        Sleep::assertSequence([Sleep::for(3)->seconds(), Sleep::for(3)->seconds()]);
        $this->assertNotNull($seen);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $seen->code);
        $this->assertTrue($user->fresh()->is_verified);
        $this->assertSame('99001122', $user->fresh()->verified_phone);
        $this->assertSame('VERIFIED', PhoneVerification::first()->status);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.verify.mn/sessions'
            && $r->hasHeader('Authorization', 'Bearer test-key')
            && preg_match('/^\d{6}$/', $r['text']) && ! isset($r['callback']));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sessions/sess-1') && ! $r->hasHeader('Authorization'));
    }

    public function test_verify_phone_returns_false_when_session_expires(): void
    {
        Sleep::fake();
        Http::fake([
            'https://api.verify.mn/sessions' => Http::response(['sessionId' => 'sess-1', 'phone' => '99001122', 'text' => '123456', 'expiresAt' => now()->addSeconds(9)->toIso8601String()], 201),
            'https://api.verify.mn/sessions/sess-1' => function () {
                // Server keeps reporting PENDING; the local expiresAt deadline must stop the loop.
                $this->travel(3)->seconds();

                return Http::response($this->sessionResponse('PENDING'));
            },
        ]);
        $user = User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);

        $this->assertFalse(app(VerifyService::class)->verifyPhone('99001122', $user));
        $this->assertFalse($user->fresh()->is_verified);
        $this->assertSame('EXPIRED', PhoneVerification::first()->status);
    }

    public function test_verify_phone_returns_false_when_api_reports_expired(): void
    {
        Sleep::fake();
        Http::fake([
            'https://api.verify.mn/sessions' => Http::response(['sessionId' => 'sess-1', 'phone' => '99001122', 'text' => '123456', 'expiresAt' => now()->addSeconds(300)->toIso8601String()], 201),
            'https://api.verify.mn/sessions/sess-1' => Http::response($this->sessionResponse('EXPIRED')),
        ]);

        $this->assertFalse(app(VerifyService::class)->verifyPhone('99001122'));
        Sleep::assertNeverSlept();
    }

    public function test_bad_api_key_fails_loudly_without_logging_the_key(): void
    {
        Http::fake(['https://api.verify.mn/sessions' => Http::response(['message' => 'Unauthorized'], 401)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('401');
        app(VerifyService::class)->verifyPhone('99001122');
    }

    public function test_missing_api_key_fails_loudly(): void
    {
        config(['verify.api_key' => null]);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('VERIFY_MN_API_KEY');
        app(VerifyService::class)->verifyPhone('99001122');
        Http::assertNothingSent();
    }

    public function test_conflict_retries_with_a_new_code(): void
    {
        Sleep::fake();
        $texts = [];
        Http::fake([
            'https://api.verify.mn/sessions' => function (Request $r) use (&$texts) {
                $texts[] = $r['text'];

                return count($texts) === 1 ? Http::response(['message' => 'active session'], 409)
                    : Http::response(['sessionId' => 'sess-1', 'phone' => '99001122', 'text' => $r['text'], 'expiresAt' => now()->addSeconds(300)->toIso8601String()], 201);
            },
            'https://api.verify.mn/sessions/sess-1' => Http::response($this->sessionResponse('VERIFIED')),
        ]);

        $this->assertTrue(app(VerifyService::class)->verifyPhone('99001122'));
        $this->assertCount(2, $texts);
        $this->assertNotSame($texts[0], $texts[1]);
    }

    public function test_api_start_check_flow_and_checkout_gate(): void
    {
        config(['verify.require_for_checkout' => true]);
        $this->fakeCreate();
        Http::fake(['https://api.verify.mn/sessions/sess-1' => Http::sequence()
            ->push($this->sessionResponse('PENDING'))
            ->push($this->sessionResponse('VERIFIED', ['verifiedAt' => now()->toIso8601String()])),
        ]);
        $user = User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);

        $this->actingAs($user)->postJson('/api/orders', ['shipping_name' => 'C'])->assertForbidden()->assertJsonPath('verification_required', true);

        $start = $this->actingAs($user)->postJson('/api/verify/start', ['phone' => '9900 1122'])->assertOk();
        $this->assertSame('sess-1', $start->json('session.session_id'));
        $this->assertStringContainsString('99001122', $start->json('session.display_instruction'));
        $this->assertSame('sms:144773?body='.$start->json('session.code'), $start->json('session.sms_uri'));

        // Starting again while the session is active reuses it (no second paid SMS).
        $this->actingAs($user)->postJson('/api/verify/start', ['phone' => '99001122'])->assertOk()->assertJsonPath('session.session_id', 'sess-1');
        Http::assertSentCount(1);

        $this->actingAs($user)->getJson('/api/verify/sessions/sess-1/check')->assertOk()->assertJsonPath('verified', false)->assertJsonPath('session.status', 'PENDING');
        $this->travel(3)->seconds();
        $this->actingAs($user)->getJson('/api/verify/sessions/sess-1/check')->assertOk()->assertJsonPath('verified', true)->assertJsonPath('session.status', 'VERIFIED');

        $user = $user->fresh();
        $this->actingAs($user)->getJson('/api/verify/status')->assertOk()->assertJsonPath('verified', true)->assertJsonPath('verified_phone', '99001122');
        $this->actingAs($user)->postJson('/api/verify/start', ['phone' => '99001122'])->assertOk()->assertJsonPath('verified', true);
    }

    public function test_callback_returns_200_quickly_and_triggers_status_check(): void
    {
        config(['verify.callback_url' => 'https://shop.test/api/verify/callback']);
        Http::fake([
            'https://api.verify.mn/sessions' => Http::response(['sessionId' => 'sess-1', 'phone' => '99001122', 'text' => '123456', 'expiresAt' => now()->addSeconds(300)->toIso8601String()], 201),
            'https://api.verify.mn/sessions/sess-1' => Http::response($this->sessionResponse('VERIFIED', ['verifiedAt' => now()->toIso8601String()])),
        ]);
        $user = User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);
        $session = app(VerifyService::class)->createSession('99001122', $user);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.verify.mn/sessions'
            && $r['callback'] === 'https://shop.test/api/verify/callback/'.$session->callback_token);

        $this->get('/api/verify/callback/'.$session->callback_token)->assertOk();
        $this->app->terminate(); // run the after-response job

        $this->assertSame('VERIFIED', $session->fresh()->status);
        $this->assertTrue($user->fresh()->is_verified);

        $this->get('/api/verify/callback/unknown-token')->assertOk();
    }

    public function test_mock_mode_simulates_the_sms(): void
    {
        config(['verify.mock' => true, 'verify.api_key' => null]);
        Http::fake();
        $user = User::create(['name' => 'C', 'email' => 'c@x.mn', 'password' => 'password', 'role' => 'customer']);

        $start = $this->actingAs($user)->postJson('/api/verify/start', ['phone' => '99001122'])->assertOk()->assertJsonPath('mock', true);
        $id = $start->json('session.session_id');
        $code = $start->json('session.code');

        $this->actingAs($user)->postJson("/api/verify/sessions/{$id}/mock-confirm", ['text' => '000000'])->assertStatus(422);
        $this->actingAs($user)->postJson("/api/verify/sessions/{$id}/mock-confirm", ['text' => " {$code} "])->assertOk()->assertJsonPath('verified', true);
        $this->assertTrue($user->fresh()->is_verified);
        Http::assertNothingSent();
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
