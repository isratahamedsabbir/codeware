<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Password reset by emailed code for API clients — the same flow the storefront
 * runs (App\Http\Controllers\Auth\PasswordResetOtpController), through the same
 * service.
 *
 * Deliberately one call rather than two. A browser can carry a redeemed code
 * between page loads in its session; an API client has nowhere to put one, so
 * asking it to hold a verified code between requests would mean trusting it with
 * a secret it can simply assert. Here the code is redeemed and the password
 * written in the same request, and there is no intermediate state to fake.
 */
class PasswordResetController extends Controller
{
    // Held to the same rules as every other password here, so an API client
    // cannot set something the storefront would have refused.
    use PasswordValidationRules;

    public function __construct(private readonly PasswordResetService $resets) {}

    /**
     * Emails a code to an address. Answers the same way whether or not the
     * account exists, and whether or not a code is already outstanding — see
     * PasswordResetService::requestCode.
     */
    public function sendCode(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        $status = $this->resets->requestCode($validated['email']);

        if ($status === OtpService::FAILED) {
            throw ValidationException::withMessages([
                'email' => [__('We could not send the code right now. Please try again in a moment.')],
            ]);
        }

        if ($status === OtpService::RATE_LIMITED) {
            // 429 rather than 422: this is a limit with an end, not a malformed
            // request, and a client that can tell the difference knows to stop
            // asking instead of trying a slightly different spelling of the same
            // call. The wording is the same either way, so it says nothing about
            // whether the account exists.
            throw ValidationException::withMessages([
                'email' => [__('You have asked for several codes already. Please wait before asking for another.')],
            ])->status(429);
        }

        // THROTTLED is not an error here, for the same reason it is not one on
        // the storefront: a cooldown only exists alongside a live code, so the
        // client is being told the truth either way, and answering "wait" to a
        // caller who already has a perfectly good code would only send the API
        // client looking for an error to wait on.
        return response()->json(['data' => ['message' => __('If that email address has an account, a code is on its way.')]]);
    }

    /**
     * Redeems a code and sets the new password in the same call.
     *
     * Everything is validated before the code is touched. Redeeming first and
     * then discovering the password is too weak would waste a code the customer
     * cannot replace for another minute, which is a punishment for a typo in
     * their own new password.
     *
     * The code itself is validated as a plain string: it is digits, but rejecting
     * a non-numeric one differently would tell an attacker that a value of the
     * right shape had been tried.
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
            'password' => $this->passwordRules(),
            // Given its own rule as well as the "confirmed" one, so the confirmed
            // value itself comes back from validate() to be handed on.
            'password_confirmation' => ['required', 'string'],
        ]);

        $status = $this->resets->verifyCode($validated['email'], $validated['code']);

        if ($status !== OtpService::VERIFIED) {
            throw ValidationException::withMessages([
                'code' => [match ($status) {
                    OtpService::LOCKED => __('Too many wrong attempts. Request a new code to continue.'),
                    OtpService::EXPIRED => __('That code has expired. Request a new one to continue.'),
                    default => __('That code is incorrect. Please try again.'),
                }],
            ]);
        }

        $this->resets->setPassword($validated['email'], $validated['password'], $validated['password_confirmation']);

        return response()->json(['data' => ['message' => __('Your password has been reset. You can now sign in with it.')]]);
    }
}
