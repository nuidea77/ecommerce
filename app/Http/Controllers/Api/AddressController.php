<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Support\Locations;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AddressController extends Controller
{
    public function locations(): JsonResponse
    {
        return response()->json(Locations::all());
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->addresses()->orderByDesc('is_default')->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $user = $request->user();

        $data['is_default'] = $data['is_default'] ?? ! $user->addresses()->exists();
        if ($data['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        }

        return response()->json($user->addresses()->create($data), 201);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $data = $this->validated($request);

        if (! empty($data['is_default'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }
        $address->update($data);

        return response()->json($address->fresh());
    }

    public function setDefault(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json($address->fresh());
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $wasDefault = $address->is_default;
        $address->delete();
        if ($wasDefault) {
            $request->user()->addresses()->latest()->first()?->update(['is_default' => true]);
        }

        return response()->json(['ok' => true]);
    }

    public static function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:40'],
            'recipient_name' => ['required', 'string', 'regex:'.AuthController::NAME_REGEX],
            'phone' => ['required', 'regex:'.Phone::REGEX],
            'province' => ['required', 'string', 'max:60'],
            'district' => ['required', 'string', 'max:60'],
            'khoroo' => ['nullable', 'string', 'max:40'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public static function messages(): array
    {
        return [
            'recipient_name.regex' => 'Хүлээн авагчийн нэр зөвхөн үсэг агуулна.',
            'phone.regex' => AuthController::PHONE_MESSAGE,
            'address.min' => 'Дэлгэрэнгүй хаягаа бүрэн бичнэ үү (байр, орц, тоот, салоны нэр).',
        ];
    }

    protected function validated(Request $request): array
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);
        $data = $request->validate(static::rules(), static::messages());

        if (! Locations::isValid($data['province'], $data['district'], $data['khoroo'] ?? null)) {
            throw ValidationException::withMessages(['district' => 'Хот/аймаг, дүүрэг/сум, хорооны мэдээлэл таарахгүй байна.']);
        }

        return $data;
    }
}
