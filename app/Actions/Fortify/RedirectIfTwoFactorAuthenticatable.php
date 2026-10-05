<?php

namespace App\Actions\Fortify;

use App\Support\Mfa;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as BaseRedirect;

/**
 * Fortify's "should this login be challenged?" step, asked the question this app
 * actually means.
 *
 * The stock action reads the TOTP columns directly, so it challenges on a
 * two_factor_secret and nothing else. That is fine for a stock Fortify install
 * and wrong for this one in two ways:
 *
 *   - a user who enrolled a *passkey* and never touched an authenticator app has
 *     no secret for it to find, so every password login of theirs would walk
 *     straight past a factor they deliberately set up;
 *   - it re-derives "is a second factor enabled" in a second place, which is how
 *     App\Support\Mfa came to exist.
 *
 * So the answer is asked once, in Mfa::mustChallenge(), and this only handles the
 * response — the same session keys, the same event, the same redirect, all
 * inherited. Swapped in for the contract in FortifyServiceProvider rather than
 * replacing the controller.
 *
 * mustChallenge() rather than hasFactorFor() on purpose: a role with the MFA
 * switch off is saying "a password is enough here", and challenging such an
 * account anyway would make the switch a one-way door — switched on once and
 * never usable again for anyone who has enrolled.
 *
 * One gap worth naming: Fortify only puts this action in the pipeline while
 * Features::twoFactorAuthentication() is on, so a deployment that switches TOTP
 * off entirely also stops passkey-only accounts being challenged at the login
 * form. They are still challenged at the three portal logins, which ask their own
 * questions without consulting Fortify's pipeline, and still blocked by
 * EnsureMfaEnforced.
 */
class RedirectIfTwoFactorAuthenticatable extends BaseRedirect
{
    public function handle($request, $next)
    {
        // Credentials are validated here, not further down the pipeline, because
        // Mfa::mustChallenge() needs the account to ask about. This is the same
        // call the parent makes, so a bad password still fails (and still trips
        // the login limiter) before anything MFA-related is considered.
        $user = $this->validateCredentials($request);

        if (Mfa::mustChallenge($user)) {
            return $this->twoFactorChallengeResponse($request, $user);
        }

        return $next($request);
    }
}
