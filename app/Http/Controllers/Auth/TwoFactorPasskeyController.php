<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Support\Mfa;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Support\WebAuthn;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Answers the two-factor challenge with a passkey, for the accounts that have
 * one.
 *
 * The package's own passkey confirmation route cannot do this: it resolves the
 * user from the authenticated guard
 * (Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController), and at
 * this point nobody is authenticated — that is the whole question. So the two
 * calls it makes are made here against the *challenged* user instead, taken from
 * the session key Fortify itself parks the half-finished login under.
 *
 * The consequence of getting that wrong is the reason this controller is so
 * narrow: `login.id` is the only thing that decides whose credentials are even
 * looked up, nothing in the request body is, and the passkey has to belong to
 * that account (VerifyPasskey::ensurePasskeyBelongsToUser). A credential that
 * verifies is only ever a second factor for the account already named by the
 * password half of the sign-in.
 *
 * The completion half is Fortify's, verbatim — same events, same guard->login,
 * same session regeneration, same TwoFactorLoginResponse — so a sign-in finished
 * here is indistinguishable from one finished by typing a 6-digit code.
 *
 * Registered on the web routes with no domain, like /two-factor-challenge
 * itself, because each portal's login parks login.id on its own host-scoped
 * session and this has to be reachable on that same host to read it.
 */
class TwoFactorPasskeyController extends Controller
{
    public function __construct(protected StatefulGuard $guard) {}

    /**
     * Hand the browser its PublicKeyCredentialRequestOptions.
     *
     * Scoped to the challenged user's own registered credentials rather than
     * left discoverable, so the challenge cannot be satisfied by a passkey
     * belonging to some other account on the same device.
     */
    public function options(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $user = $this->challengedUser($request);

        if (! Mfa::hasPasskeyFor($user)) {
            throw ValidationException::withMessages([
                'passkey' => __('No passkey is registered for this account.'),
            ]);
        }

        $options = $generate($user);

        // The session copy is not a convenience — the verifier has to check the
        // assertion against the challenge it generated, so it has to survive the
        // round trip out to the authenticator and back. Pulled (not read) on the
        // way back, which is what makes a replayed credential fail.
        $request->session()->put('passkey.verification_options', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }

    /**
     * The credential the authenticator produced: verify it, then finish the
     * sign-in.
     */
    public function store(Request $request, VerifyPasskey $verify): Response|JsonResponse
    {
        $user = $this->challengedUser($request);

        $request->validate([
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
        ]);

        $serialized = $request->session()->pull('passkey.verification_options');

        if (! $serialized) {
            throw ValidationException::withMessages([
                'credential' => __('The passkey request expired. Please try again.'),
            ]);
        }

        try {
            $credential = WebAuthn::fromJson(
                json_encode($request->input('credential'), JSON_THROW_ON_ERROR) ?: '{}',
                PublicKeyCredential::class,
            );

            $verify(
                $credential,
                WebAuthn::fromJson($serialized, PublicKeyCredentialRequestOptions::class),
                $user,
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            // laravel/passkeys throws InvalidPasskeyException for every failure
            // mode — unknown credential, wrong owner, bad signature, expired
            // challenge — and its message is deliberately generic, so this one
            // is too. Whatever went wrong, the sign-in has not happened.
            event(new TwoFactorAuthenticationFailed($user));

            throw ValidationException::withMessages([
                'credential' => __('That passkey was not accepted. Try again, or use another method.'),
            ]);
        }

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        // Fortify forgets login.id on every successful path (its TOTP and
        // recovery-code branches both do), and session()->regenerate() below
        // changes the session's id, not its contents — so without this the
        // half-finished login would still be sitting there, keeping
        // /two-factor-challenge reachable on an authenticated session.
        $request->session()->forget('login.id');

        $this->guard->login($user, $this->rememberMe($request));

        $request->session()->regenerate();

        return app(TwoFactorLoginResponse::class);
    }

    /**
     * The half-authenticated account, or a bounce back to the login that started
     * it.
     *
     * The same guard Fortify's TwoFactorLoginRequest applies, read from the same
     * session keys the three portal logins and Fortify's own login all write —
     * `login.id` names the account, `login.remember` carries the "keep me signed
     * in" checkbox across the ceremony.
     *
     * A missing id is not an error to report: it means the challenge was reached
     * without a password having been accepted (a stale tab, a cleared session), so
     * there is nothing to fail and the only correct answer is to start over.
     */
    private function challengedUser(Request $request): User
    {
        $id = $request->session()->get('login.id');

        $user = $id ? User::find($id) : null;

        if (! $user) {
            throw new HttpResponseException(redirect()->route('login'));
        }

        // A passkey is a second factor, never a first one. Reaching this screen
        // requires the password half to have already succeeded, and if it has not
        // — or the session outlived whatever made it true, such as the account
        // being blocked since — there is nothing this credential may answer.
        if ($user->is_blocked || $user->hasInactiveRole()) {
            $request->session()->forget(['login.id', 'login.remember']);

            throw new HttpResponseException(redirect()->route('login'));
        }

        return $user;
    }

    private function rememberMe(Request $request): bool
    {
        return (bool) $request->session()->get('login.remember', false);
    }
}
