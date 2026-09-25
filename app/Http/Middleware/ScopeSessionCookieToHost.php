<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives each host (main site, admin, vendor, delivery) its own session
 * cookie name. Host-only cookies (SESSION_DOMAIN=null) already keep the
 * panels' logins apart, but a same-named cookie scoped to the parent domain
 * (e.g. one left over from SESSION_DOMAIN=.codeware.test) still gets sent to
 * every subdomain ahead of the host-only one. PHP only reads the first, so
 * every request starts a fresh session and each Livewire action fails with a
 * 419 "Page Expired". A per-host name means no other host's cookie can ever
 * shadow this one.
 *
 * Must run before StartSession, so it's prepended to the `web` group.
 */
class ScopeSessionCookieToHost
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['session.cookie' => config('session.cookie').'-'.Str::slug(str_replace('.', '-', $request->getHost()))]);

        return $next($request);
    }
}
