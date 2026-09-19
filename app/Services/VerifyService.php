<?php

namespace App\Services;

use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * verify.mn — Mobile-Originated SMS phone verification.
 *
 * The API key is read from VERIFY_MN_API_KEY and is never logged. A session is
 * created with a fresh random 6-digit code; verification is confirmed ONLY by
 * GET /sessions/{id} returning sessionStatus === "VERIFIED" (the callback is a
 * wake-up signal, the reply SMS is carrier-dependent and is never trusted).
 */
class VerifyService
{
    public const PROVIDER = 'verify.mn';

    public function isMock(): bool
    {
        return (bool) config('verify.mock') && ! config('verify.api_key');
    }

    /**
     * Verify a phone number end-to-end: create a session and poll every 3s
     * until it is VERIFIED (true) or expires / times out (false).
     *
     * @param  callable|null  $onSession  receives the PhoneVerification right after creation,
     *                                    so the caller can show displayInstruction / smsUri.
     */
    public function verifyPhone(string $phone, ?User $user = null, ?callable $onSession = null): bool
    {
        $verification = $this->createSession($phone, $user);

        if ($onSession) {
            $onSession($verification);
        }

        return $this->waitForVerification($verification);
    }

    /**
     * Block until the session is VERIFIED or expired. Never polls faster than
     * the configured interval and never past the session's expiresAt.
     */
    public function waitForVerification(PhoneVerification $verification, ?int $hardTimeoutSeconds = null): bool
    {
        $interval = max(3, (int) config('verify.poll_interval', 3));
        $deadline = min(
            $verification->expires_at?->getTimestamp() ?? PHP_INT_MAX,
            now()->addSeconds($hardTimeoutSeconds ?? config('verify.session_ttl', 300))->getTimestamp(),
        );

        while (true) {
            $verification = $this->checkSession($verification);

            if ($verification->isVerified()) {
                return true;
            }
            if ($verification->isExpired() || now()->getTimestamp() >= $deadline) {
                if ($verification->status !== PhoneVerification::EXPIRED) {
                    $verification->update(['status' => PhoneVerification::EXPIRED]);
                }

                return false;
            }

            Sleep::for($interval)->seconds();
        }
    }

    /**
     * POST /sessions — start a verification with a new one-time code.
     */
    public function createSession(string $phone, ?User $user = null): PhoneVerification
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($phone) < 8 || strlen($phone) > 16) {
            throw new RuntimeException('Утасны дугаар 8–16 оронтой байх ёстой.');
        }

        // Reuse a still-active session for the same user+phone so the user is not
        // asked to pay for a second SMS while the first code is still valid.
        $active = PhoneVerification::where('phone', $phone)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->where('status', PhoneVerification::PENDING)->where('expires_at', '>', now())
            ->latest()->first();
        if ($active) {
            return $active;
        }

        $callbackToken = config('verify.callback_url') ? Str::random(48) : null;

        if ($this->isMock()) {
            return PhoneVerification::create([
                'user_id' => $user?->id,
                'phone' => $phone,
                'session_id' => 'mock-'.Str::uuid(),
                'code' => $this->generateCode(),
                'callback_token' => $callbackToken,
                'sms_uri' => null,
                'display_instruction' => "[Туршилтын горим] {$phone} дугаараас 144773 руу доорх кодыг илгээнэ үү.",
                'status' => PhoneVerification::PENDING,
                'expires_at' => now()->addSeconds(config('verify.session_ttl', 300)),
            ]);
        }

        $attempt = 0;
        do {
            $code = $this->generateCode();
            $payload = array_filter([
                'phone' => $phone,
                'text' => $code,
                'callback' => $callbackToken ? $this->callbackUrl($callbackToken) : null,
                'responseSms' => config('verify.response_sms') ?: null,
            ]);

            $response = $this->client()->post('/sessions', $payload);
            // 409 = an active session already exists for this phone+text; retry with a new code.
        } while ($response->status() === 409 && ++$attempt < 3);

        $this->throwIfFailed($response, 'create session');
        $data = $response->json();

        $verification = PhoneVerification::create([
            'user_id' => $user?->id,
            'phone' => $data['phone'] ?? $phone,
            'session_id' => $data['sessionId'],
            'code' => $code,
            'callback_token' => $callbackToken,
            'sms_uri' => $data['smsUri'] ?? null,
            'display_instruction' => $data['displayInstruction'] ?? null,
            'status' => PhoneVerification::PENDING,
            'expires_at' => isset($data['expiresAt']) ? Carbon::parse($data['expiresAt']) : now()->addSeconds(config('verify.session_ttl', 300)),
        ]);

        Log::info('verify.mn session created', ['session_id' => $verification->session_id, 'user_id' => $user?->id]);

        return $verification;
    }

    /**
     * GET /sessions/{id} — the only trusted source of truth. Marks the linked
     * user verified when the session is VERIFIED.
     */
    public function checkSession(PhoneVerification $verification): PhoneVerification
    {
        if ($verification->isVerified()) {
            return $verification;
        }

        if ($this->isMock()) {
            if ($verification->status === PhoneVerification::PENDING && $verification->expires_at?->isPast()) {
                $verification->update(['status' => PhoneVerification::EXPIRED]);
            }

            return $verification->fresh();
        }

        $response = $this->client(auth: false)->get('/sessions/'.$verification->session_id);

        if ($response->status() === 404) {
            $verification->update(['status' => PhoneVerification::EXPIRED, 'last_checked_at' => now()]);

            return $verification->fresh();
        }
        if (! $response->successful()) {
            Log::warning('verify.mn status check failed', ['session_id' => $verification->session_id, 'status' => $response->status()]);
            $verification->update(['last_checked_at' => now()]);

            return $verification->fresh();
        }

        $data = $response->json();
        $status = $data['sessionStatus'] ?? PhoneVerification::PENDING;

        $verification->update([
            'status' => in_array($status, [PhoneVerification::PENDING, PhoneVerification::VERIFIED, PhoneVerification::EXPIRED], true) ? $status : PhoneVerification::PENDING,
            'callback_status' => $data['callbackStatus'] ?? $verification->callback_status,
            'verified_at' => ! empty($data['verifiedAt']) ? Carbon::parse($data['verifiedAt']) : $verification->verified_at,
            'expires_at' => ! empty($data['expiresAt']) ? Carbon::parse($data['expiresAt']) : $verification->expires_at,
            'last_checked_at' => now(),
        ]);

        if ($status === PhoneVerification::VERIFIED) {
            $this->markVerified($verification);
        }

        return $verification->fresh();
    }

    /** Mock only: simulate the user's SMS arriving at 144773. */
    public function mockConfirm(PhoneVerification $verification, string $text): PhoneVerification
    {
        abort_unless($this->isMock(), 404);

        if (! $verification->isActive()) {
            throw new RuntimeException('Энэ код хүчингүй болсон. Шинэ код авна уу.');
        }
        if (trim($text) !== $verification->code) {
            throw new RuntimeException('Илгээсэн код таарахгүй байна.');
        }

        $verification->update(['status' => PhoneVerification::VERIFIED, 'verified_at' => now(), 'last_checked_at' => now()]);
        $this->markVerified($verification);

        return $verification->fresh();
    }

    public function markVerified(PhoneVerification $verification): void
    {
        if ($verification->verified_at === null) {
            $verification->update(['verified_at' => now()]);
        }

        $user = $verification->user;
        if ($user && ! $user->is_verified) {
            $user->forceFill([
                'is_verified' => true,
                'verified_at' => $verification->verified_at ?? now(),
                'verify_provider' => $this->isMock() ? self::PROVIDER.'-mock' : self::PROVIDER,
                'verified_phone' => $verification->phone,
                'phone' => $user->phone ?: $verification->phone,
            ])->save();
            Log::info('verify.mn phone verified', ['session_id' => $verification->session_id, 'user_id' => $user->id]);
        }
    }

    public function callbackUrl(string $token): string
    {
        return rtrim(config('verify.callback_url'), '/').'/'.$token;
    }

    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function client(bool $auth = true): PendingRequest
    {
        $request = Http::baseUrl(config('verify.base_url'))
            ->acceptJson()->asJson()
            ->timeout((int) config('verify.http_timeout', 10));

        if ($auth) {
            $key = config('verify.api_key');
            if (! $key) {
                throw new RuntimeException('VERIFY_MN_API_KEY is not configured. Set it in .env (see .env.example).');
            }
            $request = $request->withToken($key);
        }

        return $request;
    }

    protected function throwIfFailed(Response $response, string $action): void
    {
        if ($response->successful()) {
            return;
        }

        $message = match ($response->status()) {
            400 => 'verify.mn rejected the request: '.($response->json('message') ?? 'validation error'),
            401 => 'verify.mn rejected the API key (401). Check VERIFY_MN_API_KEY.',
            409 => 'An active verify.mn session already exists for this phone.',
            default => "verify.mn {$action} failed with HTTP {$response->status()}.",
        };

        // Body may echo request data; status + action are enough for diagnostics.
        Log::error("verify.mn {$action} failed", ['status' => $response->status()]);

        throw new RuntimeException($message);
    }
}
