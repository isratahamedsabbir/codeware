<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // /admin has no ->domain() restriction of its own (see bootstrap/app.php),
        // so without this check it would still resolve on the vendor portal's
        // host too — this keeps the two panels fully separate, as if /admin
        // simply didn't exist there, rather than just denying access to it.
        abort_if($request->getHost() === config('app.vendor_host'), 404);

        // Gate::authorize() (not denies()+abort()) so a denial throws the same
        // AuthorizationException every other permission check in the app does —
        // that's what UnauthorizedAccessNotifier listens for (see bootstrap/app.php).
        Gate::authorize('access-admin');

        return $next($request);
    }
}
