<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiMfa;
use App\Support\ApiUser;
use App\Support\Mfa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Support\WebAuthn;
use Throwable;
use Webauthn\PublicKeyCredential;

/**
 * Answers the API's second-factor challenge — the stateless counterpart to
 * Fortify's /two-factor-challenge and to
 * App\Http\Controllers\Auth\TwoFactorPasskeyController.
 *
 * What is shared with the web flow is the *checking*: TOTP verification is
 * Fortify's own provider against the same encrypted secret, recovery codes are
 * compared with the same hash_equals, and a passkey is verified by the package's
 * VerifyPasskey against options from its GenerateVerificationOptions. What
 * differs is only where "whose challenge is this" comes from — a client-held
 * encrypted token (App\Support\ApiMfa) rather than `login.id` in a session — and
 * that the prize is a Sanctum token rather than a signed-in session.
 *
 * The challenge token is the only thing naming the account, exactly as
 * `login.id` is on the web. Nothing in this request body chooses whose factor is
 * being answered, and VerifyPasskey is handed the resolved user so a passkey
 * belonging to a different account on the same device cannot satisfy it.
 */
class MfaController extends Controller
{
    /**
     * A fresh set of WebAuthn options for a challenge already in flight.
     *
     * Only needed when the first ceremony timed out: the options minted with the
     * challenge carry the authenticator's own short timeout, and a user who does
     * not reach for their phone within it has to start that half again. They come
     * back inside a *new* challenge token, which the client must use from here
     * on — the old token still holds the expired options, so keeping one token
     * for the whole sign-in and swapping only the options would hand the verifier
     * a challenge the assertion was never made against.
     */
    public function options(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $request->validate(['mfa_token' => ['required', 'string']]);

        $user = $this->challengedUser($request->input('mfa_token'));

        if (! in_array(ApiMfa::METHOD_PASSKEY, ApiMfa::methodsFor($user), true)) {
            throw ValidationException::withMessages([
                'mfa_token' => [__('This account cannot be verified with a passkey.')],
            ]);
        }

        // Generated exactly once and reused for both the token and the response:
        // the client must be handed the options that the token it is about to
        // carry were built from, not a second, unrelated set.
        $options = $generate($user);

        return response()->json([
            'data' => [
                'mfa_token' => ApiMfa::issueWithPasskeyOptions($user, WebAuthn::toJson($options)),
                'passkey_options' => WebAuthn::toBrowserArray($options),
                'expires_in' => ApiMfa::TTL_MINUTES * 60,
            ],
        ]);
    }

    /**
     * Verify a second factor and, if it holds, hand back the token the caller
     * came for.
     */
    public function store(Request $request, VerifyPasskey $verify, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $request->validate([
            'mfa_token' => ['required', 'string'],
            'code' => ['nullable', 'string', 'required_without_all:recovery_code,credential'],
            'recovery_code' => ['nullable', 'string', 'required_without_all:code,credential'],
            'credential' => ['nullable', 'array', 'required_without_all:code,recovery_code'],
            'credential.id' => ['required_with:credential', 'string'],
            'credential.rawId' => ['required_with:credential', 'string'],
            'credential.type' => ['required_with:credential', 'string', 'in:public-key'],
            'credential.response' => ['required_with:credential', 'array'],
        ]);

        $user = $this->challengedUser($request->input('mfa_token'));

        $verified = match (true) {
            filled($request->input('credential')) => $this->verifyPasskey($request, $verify, $user),
            filled($request->input('recovery_code')) => $this->verifyRecoveryCode($request, $user),
            default => $this->verifyTotp($request, $user, $provider),
        };

        if (! $verified) {
            // The same event the web challenge fires, for the same reason: a
            // refused second factor is worth counting, and anything already
            // listening expects the web flow to report here too.
            event(new TwoFactorAuthenticationFailed($user));

            // One message for all three methods, naming none of them. Telling the
            // caller which method was close is free information for someone
            // probing an account they do not hold the password for.
            throw ValidationException::withMessages([
                'code' => [__('That second factor was not accepted. Try again.')],
            ]);
        }

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        return response()->json([
            'data' => [
                'token' => $user->createToken('customer-api')->plainTextToken,
                'user' => ApiUser::payload($user),
            ],
        ]);
    }

    /**
     * The account a challenge token names, or a validation failure saying the
     * challenge can no longer be answered.
     *
     * 422 rather than 401: the password was accepted and the caller did nothing
     * wrong — the challenge has gone stale (expired, already answered, or minted
     * against an account that has since been blocked). The client's answer is to
     * sign in again, which is what its own 401 handling already does.
     */
    private function challengedUser(string $token): User
    {
        $user = ApiMfa::resolve($token);

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'mfa_token' => [__('This two-factor challenge has expired. Please sign in again.')],
            ]);
        }

        return $user;
    }

    /**
     * A TOTP code from an authenticator app, checked by Fortify's own provider so
     * the API's window and replay behaviour are the web login's rather than a
     * second interpretation of them.
     */
    private function verifyTotp(Request $request, User $user, TwoFactorAuthenticationProvider $provider): bool
    {
        if (! Mfa::totpEnabledFor($user)) {
            return false;
        }

        return (bool) $provider->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            (string) $request->input('code')
        );
    }

    /**
     * One of the printed recovery codes, compared in constant time against the
     * stored set.
     *
     * Note what is *not* here: no invalidation of the code just used. Fortify
     * makes recovery codes single-use by regenerating the whole set, which is
     * driven from the profile screen — so a code redeemed over the API stays
     * valid until it is regenerated there, exactly as it would if the same code
     * were typed into a browser. Regenerating on redemption instead would mean
     * the API could invalidate codes the web flow still considers live, with
     * nothing in the browser to notice.
     */
    private function verifyRecoveryCode(Request $request, User $user): bool
    {
        if (! Mfa::totpEnabledFor($user)) {
            return false;
        }

        $submitted = (string) $request->input('recovery_code');

        return collect($user->recoveryCodes())
            ->contains(fn (string $code) => hash_equals($code, $submitted));
    }

    /**
     * A WebAuthn assertion, verified against the options this challenge was
     * minted with and bound to the resolved account.
     */
    private function verifyPasskey(Request $request, VerifyPasskey $verify, User $user): bool
    {
        $options = ApiMfa::toRequestOptions(ApiMfa::passkeyOptions($request->input('mfa_token')));

        if (! $options instanceof PublicKeyCredentialRequestOptions) {
            return false;
        }

        try {
            $credential = WebAuthn::fromJson(
                json_encode($request->input('credential'), JSON_THROW_ON_ERROR) ?: '{}',
                PublicKeyCredential::class,
            );

            $verify($credential, $options, $user);
        } catch (Throwable) {
            // laravel/passkeys throws InvalidPasskeyException for every failure
            // mode — unknown credential, wrong owner, bad signature, expired
            // challenge — and the WebAuthn library throws its own for a malformed
            // body. All of it means the same one thing here.
            return false;
        }

        return true;
    }
}
