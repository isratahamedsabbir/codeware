<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function store(Request $request, CreateNewUser $creator): JsonResponse
    {
        $user = $creator->create($request->all());

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('customer-api')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $this->formatUser($user),
            ],
        ], 201);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
        ];
    }
}
