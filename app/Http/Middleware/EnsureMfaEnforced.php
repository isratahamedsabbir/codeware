<?php

namespace App\Http\Middleware;

use App\Support\Mfa;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds an account at the door until it has a second factor, once one of its
 * roles has MFA switched on (Admin → Roles).
 *
 * The other half of the login story lives at the login forms: they check
 * Mfa::mustChallenge() and send a user who already has a factor to the
 * challenge. This is the other case, and it cannot be handled there — an account
 * with *no* factor has nothing to be challenged with, so it has to be walked
 * through enrolment first, and it can arrive here without ever having tried:
 *
 *   - the policy was switched on while it had a session open;
 *   - its TOTP was removed (a phone reset, an account cleaned up by hand);
 *   - its passkeys were all deleted, on another device.
 *
 * Applied per portal (routes/admin.php, routes/vendor.php, routes/delivery.php)
 * rather than to the whole 'web' group, because the policy is per audience and
 * the audience is decided by the host the request landed on.
 *
 * Not a redirect to the challenge: there is nothing to answer. This sends the
 * user to the enrolment screen instead, which is deliberately exempt from this
 * middleware on all three portals — otherwise enforcing MFA would be the thing
 * that made it impossible to satisfy.
 */
class EnsureMfaEnforced
{
    /**
     * Requests that must still answer while enforcement is on.
     *
     * The enrolment screen and the profile that carries it, because both are
     * where a factor gets added; anything under 'user/passkeys' or
     * '/two-factor-challenge', which are the routes those screens drive; and
     * logging out, which has to keep working or a user who cannot satisfy the
     * policy has no way to end the session at all.
     */
    private const EXEMPT_PREFIXES = [
        'logout',
        'two-factor-challenge',
        'user/passkeys',
        'user/two-factor',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Guests belong to the login form's business, not this one's, and
        // nothing here has an opinion about an account with no user attached.
        if (! $user || $this->isExempt($request)) {
            return $next($request);
        }

        if (! Mfa::needsEnrolmentFor($user)) {
            return $next($request);
        }

        // The profile screen hosts the panel, so it is allowed through rather
        // than exempted by name above: it is the one page a user lands on
        // deliberately to fix this, and it is reached as a guest-protected
        // portal route.
        if ($request->is('profile')) {
            return $next($request);
        }

        return redirect()->route($this->enrolmentRoute());
    }

    /**
     * Where to send the account, which is the enrolment screen on whichever
     * host the request arrived at — so the panel, the passkey routes it drives
     * and the session all stay on the host whose policy is being enforced.
     */
    private function enrolmentRoute(): string
    {
        return match (request()->getHost()) {
            config('app.vendor_host') => 'vendor.mfa.required',
            config('app.delivery_host') => 'delivery.mfa.required',
            default => 'admin.mfa.required',
        };
    }

    private function isExempt(Request $request): bool
    {
        $path = trim($request->path(), '/');

        if ($path === 'mfa-required') {
            return true;
        }

        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
