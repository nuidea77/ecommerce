<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VerifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class VerifyController extends Controller
{
    public function __construct(protected VerifyService $verify) {}

    public function status(Request $request): JsonResponse
    {
        $u = $request->user();

        return response()->json([
            'verified' => (bool) $u->is_verified,
            'verified_at' => $u->verified_at,
            'provider' => $u->verify_provider,
            'register_number' => $u->register_number ? Str::mask($u->register_number, '*', 2, 6) : null,
            'first_name' => $u->first_name,
            'last_name' => $u->last_name,
            'mock' => $this->verify->isMock(),
            'required_for_checkout' => (bool) config('verify.require_for_checkout'),
        ]);
    }

    /** Step 1: hand the SPA the verify.mn URL to redirect to. */
    public function start(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->is_verified) {
            return response()->json(['verified' => true]);
        }

        $state = Str::random(40);
        $request->session()->put('verify.state', $state);
        $request->session()->put('verify.user_id', $user->id);

        return response()->json(['url' => $this->verify->authorizationUrl($state), 'mock' => $this->verify->isMock()]);
    }

    /** Step 2: verify.mn redirects back here with ?code=&state=. */
    public function callback(Request $request): RedirectResponse
    {
        $state = $request->session()->pull('verify.state');
        $userId = $request->session()->pull('verify.user_id');
        $user = $request->user();

        if (! $user || ! $state || ! hash_equals($state, (string) $request->query('state')) || $user->id !== $userId) {
            return redirect('/verify?status=error&reason=state');
        }

        if ($request->query('error')) {
            return redirect('/verify?status=error&reason='.urlencode($request->query('error')));
        }

        try {
            $profile = $this->verify->fetchProfile((string) $request->query('code'));
            $this->verify->markVerified($user, $profile);
        } catch (Throwable $e) {
            return redirect('/verify?status=error&reason='.urlencode($e->getMessage()));
        }

        return redirect('/verify?status=success');
    }

    /** Mock provider (VERIFY_MOCK=true): the local simulation page posts here. */
    public function mockComplete(Request $request): JsonResponse
    {
        abort_unless($this->verify->isMock(), 404);

        $data = $request->validate([
            'state' => ['required', 'string'],
            'register_number' => ['required', 'string', 'max:16'],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
        ]);

        $state = $request->session()->pull('verify.state');
        $request->session()->forget('verify.user_id');
        if (! $state || ! hash_equals($state, $data['state'])) {
            throw ValidationException::withMessages(['state' => 'Хүчингүй хүсэлт. Дахин эхлүүлнэ үү.']);
        }
        if (! VerifyService::isValidRegisterNumber($data['register_number'])) {
            throw ValidationException::withMessages(['register_number' => 'Регистрийн дугаар буруу байна (ж: УБ95010112).']);
        }

        try {
            $profile = $this->verify->normalize([
                'sub' => 'mock-'.Str::uuid(),
                'register_number' => $data['register_number'],
                'last_name' => $data['last_name'],
                'first_name' => $data['first_name'],
                'phone_number' => $request->user()->phone,
                'verified_by' => 'verify.mn (mock)',
            ]);
            $this->verify->markVerified($request->user(), $profile, 'verify.mn-mock');
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['register_number' => $e->getMessage()]);
        }

        return response()->json(['verified' => true, 'user' => $request->user()->fresh()]);
    }
}
