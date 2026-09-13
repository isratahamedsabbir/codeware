<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cuts off a blocked account (Admin → Users) on every request, not just at
 * login — catches a "remember me" cookie silently re-authenticating a
 * blocked user via its token, which never runs through the credential
 * checks in FortifyServiceProvider or Vendor\Auth\Login. Applied to the
 * whole 'web' group (see bootstrap/app.php) so it reaches every host: admin,
 * vendor portal, and the plain site — blocking is an account-level lock, not
 * scoped to one panel the way a deactivated role is.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->is_blocked) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Your account has been blocked.');
        }

        return $next($request);
    }
}
