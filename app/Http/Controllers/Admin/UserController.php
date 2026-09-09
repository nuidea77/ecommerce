<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::withCount('orders')->withCount([
            'deliveries as active_deliveries_count' => fn ($q) => $q->whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit']),
            'deliveries as delivered_count' => fn ($q) => $q->where('delivery_status', 'delivered'),
        ]);

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('phone', 'like', "%$q%"));
        }

        return response()->json($query->latest()->paginate((int) $request->query('per_page', 15))->withQueryString());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::in(['admin', 'customer', 'courier'])],
            'password' => ['required', Password::min(6)],
            'is_active' => ['boolean'],
        ]);

        return response()->json(User::create($data), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::in(['admin', 'customer', 'courier'])],
            'password' => ['nullable', Password::min(6)],
            'is_active' => ['boolean'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($user->id === $request->user()->id && ($data['role'] !== 'admin' || ! ($data['is_active'] ?? true))) {
            return response()->json(['message' => 'Өөрийн админ эрхийг өөрчлөх боломжгүй.'], 422);
        }

        $user->update($data);

        return response()->json($user->fresh());
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Өөрийгөө устгах боломжгүй.'], 422);
        }
        $user->delete();

        return response()->json(['ok' => true]);
    }
}
