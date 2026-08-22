<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * The `signed` route middleware already validates the expiry/signature query
     * params; the sha1(email) hash check here is the same defense-in-depth Laravel's
     * own EmailVerificationRequest does, so a leaked/forwarded id can't verify a
     * different address than the one the link was actually generated for.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'Invalid verification link.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response()->json(['data' => ['message' => 'Email verified successfully']]);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['data' => ['message' => 'Email already verified']]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['data' => ['message' => 'Verification link sent']]);
    }
}
