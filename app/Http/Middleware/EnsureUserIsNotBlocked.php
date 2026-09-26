<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cuts off a blocked account (Admin → Users) or one holding a deactivated
 * role (Admin → Roles, see User::hasInactiveRole()) on every request, not
 * just at login — catches a "remember me" cookie silently re-authenticating
 * the user via its token, which never runs through the credential checks in
 * FortifyServiceProvider or the Vendor/Delivery logins. Applied to the whole
 * 'web' group (see bootstrap/app.php) so it reaches every host: admin,
 * vendor portal, delivery portal and the plain site. Also applied to the
 * 'api' group with the 'sanctum' guard, where the bearer token that was
 * used is revoked instead of a session being ended.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next, string $guard = 'web'): Response
    {
        $user = Auth::guard($guard)->user();

        if (! $user) {
            return $next($request);
        }

        $message = match (true) {
            (bool) $user->is_blocked => 'Your account has been blocked.',
            $user->hasInactiveRole() => 'Your account access has been disabled.',
            default => null,
        };

        if ($message === null) {
            return $next($request);
        }

        if ($guard === 'web') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } elseif (($token = $user->currentAccessToken()) instanceof PersonalAccessToken) {
            $token->delete();
        }

        abort(403, $message);
    }
}
