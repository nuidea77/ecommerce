<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CheckPhoneVerification;
use App\Models\PhoneVerification;
use App\Services\VerifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VerifyController extends Controller
{
    public function __construct(protected VerifyService $verify) {}

    public function status(Request $request): JsonResponse
    {
        $u = $request->user();
        $active = $u->phoneVerifications()->where('status', PhoneVerification::PENDING)->where('expires_at', '>', now())->latest()->first();

        return response()->json([
            'verified' => (bool) $u->is_verified,
            'verified_at' => $u->verified_at,
            'verified_phone' => $u->verified_phone,
            'provider' => $u->verify_provider,
            'session' => $active ? $this->sessionPayload($active) : null,
            'shortcode' => config('verify.shortcode'),
            'mock' => $this->verify->isMock(),
            'required_for_checkout' => (bool) config('verify.require_for_checkout'),
        ]);
    }

    /** Create (or reuse) a session; the SPA shows displayInstruction + smsUri. */
    public function start(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->is_verified) {
            return response()->json(['verified' => true]);
        }

        // Verification must come from the SIM registered on the account.
        if (! $user->phone) {
            throw ValidationException::withMessages(['phone' => 'Бүртгэлд утасны дугаар байхгүй байна.']);
        }

        try {
            $session = $this->verify->createSession($user->phone, $user);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['phone' => $e->getMessage()]);
        }

        return response()->json(['session' => $this->sessionPayload($session), 'mock' => $this->verify->isMock()]);
    }

    /** Polled by the SPA every 3s: re-checks GET /sessions/{id}. */
    public function check(Request $request, string $sessionId): JsonResponse
    {
        $session = $request->user()->phoneVerifications()->where('session_id', $sessionId)->firstOrFail();

        // Don't hammer the API if the client polls faster than allowed.
        if (! $session->isVerified() && (! $session->last_checked_at || $session->last_checked_at->diffInSeconds(now()) >= config('verify.poll_interval', 3) - 1)) {
            $session = $this->verify->checkSession($session);
        } elseif ($session->isActive() === false && $session->status === PhoneVerification::PENDING) {
            $session->update(['status' => PhoneVerification::EXPIRED]);
            $session->refresh();
        }

        return response()->json(['session' => $this->sessionPayload($session), 'verified' => (bool) $request->user()->fresh()->is_verified]);
    }

    /**
     * verify.mn -> us: GET, no body, no signature. Only a wake-up signal; the
     * real status is fetched from GET /sessions/{id} after responding 200.
     */
    public function callback(string $token): JsonResponse
    {
        $verification = PhoneVerification::where('callback_token', $token)->first();

        if ($verification && ! $verification->isVerified()) {
            CheckPhoneVerification::dispatch($verification->id)->afterResponse();
        } else {
            Log::info('verify.mn callback for unknown or completed session');
        }

        return response()->json(['ok' => true]);
    }

    /** Mock only: simulate the user's SMS reaching 144773. */
    public function mockConfirm(Request $request, string $sessionId): JsonResponse
    {
        abort_unless($this->verify->isMock(), 404);
        $data = $request->validate(['text' => ['required', 'string', 'max:120']]);
        $session = $request->user()->phoneVerifications()->where('session_id', $sessionId)->firstOrFail();

        try {
            $session = $this->verify->mockConfirm($session, $data['text']);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['text' => $e->getMessage()]);
        }

        return response()->json(['session' => $this->sessionPayload($session), 'verified' => true, 'user' => $request->user()->fresh()]);
    }

    protected function sessionPayload(PhoneVerification $s): array
    {
        return [
            'session_id' => $s->session_id,
            'phone' => $s->phone,
            'code' => $s->code,
            'sms_uri' => $s->sms_uri ?: 'sms:'.config('verify.shortcode').'?body='.rawurlencode($s->code),
            'display_instruction' => $s->display_instruction,
            'status' => $s->isActive() || $s->isVerified() ? $s->status : PhoneVerification::EXPIRED,
            'expires_at' => $s->expires_at,
            'verified_at' => $s->verified_at,
        ];
    }
}
