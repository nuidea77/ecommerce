<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Cyrillic or Latin letters, spaces, hyphens and apostrophes; 2-50 chars. */
    public const NAME_REGEX = '/^[\p{L}][\p{L}\s\'\-\.]{1,49}$/u';

    public const PHONE_MESSAGE = 'Утасны дугаар 8 оронтой, 6-9-өөр эхэлсэн байх ёстой (ж: 99001122).';

    protected function passwordRule(): Password
    {
        return Password::min(8)->letters()->numbers();
    }

    public function register(Request $request): JsonResponse
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        $data = $request->validate([
            'name' => ['required', 'string', 'regex:'.self::NAME_REGEX],
            'phone' => ['required', 'regex:'.Phone::REGEX, 'unique:users,phone'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
        ], [
            'name.regex' => 'Нэр зөвхөн үсэг, зай, зураас агуулна (2-50 тэмдэгт).',
            'phone.regex' => self::PHONE_MESSAGE,
            'phone.unique' => 'Энэ дугаараар бүртгэл үүссэн байна. Нэвтэрнэ үү.',
            'password.min' => 'Нууц үг хамгийн багадаа 8 тэмдэгт байна.',
            'password.letters' => 'Нууц үг дор хаяж нэг үсэг агуулна.',
            'password.numbers' => 'Нууц үг дор хаяж нэг тоо агуулна.',
            'password.confirmed' => 'Нууц үг давталт таарахгүй байна.',
        ]);

        $user = User::create($data + ['role' => User::ROLE_CUSTOMER]);
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user, 'verification_required' => true], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        $credentials = $request->validate([
            'phone' => ['required', 'regex:'.Phone::REGEX],
            'password' => ['required', 'string'],
        ], ['phone.regex' => self::PHONE_MESSAGE]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'phone' => ['Утасны дугаар эсвэл нууц үг буруу байна.'],
            ]);
        }

        if (! $request->user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['phone' => ['Таны бүртгэл идэвхгүй байна.']]);
        }

        $request->session()->regenerate();

        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'regex:'.self::NAME_REGEX],
            'email' => ['nullable', 'email:rfc', 'max:190', 'unique:users,email,'.$user->id],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'confirmed', $this->passwordRule()],
        ], ['name.regex' => 'Нэр зөвхөн үсэг, зай, зураас агуулна (2-50 тэмдэгт).']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json(['user' => $user->fresh()]);
    }
}
