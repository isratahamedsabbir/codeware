<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Same lockouts as the web login (FortifyServiceProvider) — a correct
        // password is not enough for a blocked account or a deactivated role.
        if ($user->is_blocked || $user->hasInactiveRole()) {
            throw ValidationException::withMessages([
                'email' => [$user->is_blocked ? 'Your account has been blocked.' : 'Your account access has been disabled.'],
            ]);
        }

        $token = $user->createToken('customer-api')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => ['message' => 'Logged out successfully']]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'email_verified_at_display' => $user->email_verified_at?->toDisplay(),
        ];
    }
}
