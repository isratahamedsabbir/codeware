<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('users')->sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['data' => ['message' => __($status)]]);
    }

    public function reset(Request $request, ResetUserPassword $resetter): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            fn ($user, $password) => $resetter->reset($user, $request->only('password', 'password_confirmation')),
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['data' => ['message' => __($status)]]);
    }
}
