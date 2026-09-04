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
        // Gate::authorize() (not denies()+abort()) so a denial throws the same
        // AuthorizationException every other permission check in the app does —
        // that's what UnauthorizedAccessNotifier listens for (see bootstrap/app.php).
        Gate::authorize('access-admin');

        return $next($request);
    }
}
