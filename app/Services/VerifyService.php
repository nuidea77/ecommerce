<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * verify.mn identity verification (OAuth 2.0 authorization-code adapter).
 */
class VerifyService
{
    public function isMock(): bool
    {
        return (bool) config('verify.mock') || ! config('verify.client_id');
    }

    public function redirectUri(): string
    {
        return config('verify.redirect_uri') ?: url('/api/verify/callback');
    }

    /** Build the URL the customer is sent to. */
    public function authorizationUrl(string $state): string
    {
        if ($this->isMock()) {
            return url('/verify/mock?state='.$state);
        }

        return config('verify.authorize_url').'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('verify.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'scope' => config('verify.scopes'),
            'state' => $state,
        ]);
    }

    /** Exchange the authorization code and fetch the verified profile. */
    public function fetchProfile(string $code): array
    {
        $token = Http::asForm()->acceptJson()->post(config('verify.token_url'), [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
            'client_id' => config('verify.client_id'),
            'client_secret' => config('verify.client_secret'),
        ]);

        if (! $token->successful() || ! $token->json('access_token')) {
            Log::error('verify.mn token exchange failed', ['body' => $token->body()]);
            throw new RuntimeException('verify.mn token exchange failed');
        }

        $info = Http::withToken($token->json('access_token'))->acceptJson()->get(config('verify.userinfo_url'));

        if (! $info->successful()) {
            Log::error('verify.mn userinfo failed', ['body' => $info->body()]);
            throw new RuntimeException('verify.mn userinfo failed');
        }

        return $this->normalize($info->json());
    }

    /** Map provider claims onto our profile shape. */
    public function normalize(array $claims): array
    {
        $c = config('verify.claims');

        return [
            'subject' => (string) data_get($claims, $c['subject'], ''),
            'register_number' => Str::upper((string) data_get($claims, $c['register_number'], '')),
            'last_name' => (string) data_get($claims, $c['last_name'], ''),
            'first_name' => (string) data_get($claims, $c['first_name'], ''),
            'phone' => (string) data_get($claims, $c['phone'], ''),
            'raw' => $claims,
        ];
    }

    public function markVerified(User $user, array $profile, string $provider = 'verify.mn'): User
    {
        if ($profile['register_number'] === '' || $profile['first_name'] === '') {
            throw new RuntimeException('Баталгаажуулалтын мэдээлэл дутуу байна.');
        }

        $existing = User::where('register_number', $profile['register_number'])->where('id', '!=', $user->id)->first();
        if ($existing) {
            throw new RuntimeException('Энэ регистрийн дугаараар өөр бүртгэл баталгаажсан байна.');
        }

        $user->forceFill([
            'is_verified' => true,
            'verified_at' => now(),
            'verify_provider' => $provider,
            'verify_subject' => $profile['subject'] ?: null,
            'register_number' => $profile['register_number'],
            'last_name' => $profile['last_name'],
            'first_name' => $profile['first_name'],
            'phone' => $user->phone ?: ($profile['phone'] ?: null),
            'verify_data' => $profile['raw'] ?? [],
        ])->save();

        return $user;
    }

    /** Mongolian register number: 2 Cyrillic letters + 8 digits (e.g. УБ95010112). */
    public static function isValidRegisterNumber(string $value): bool
    {
        return (bool) preg_match('/^[А-ЯӨҮЁ]{2}\d{8}$/u', Str::upper($value));
    }
}
