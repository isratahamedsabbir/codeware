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
        $name = self::nameFor($request->getHost());

        config(['session.cookie' => $name]);

        // Fortify's own service provider resolves the 'web' guard while
        // *registering* (to bind its login-response redirects), and Laravel
        // reads a route's controller middleware before the first middleware
        // runs — so on the storefront's Fortify routes (/login, /register,
        // /forgot-password, ...) the session store is already built by the
        // time this middleware gets here, under the un-suffixed name, and
        // SessionManager caches it for the rest of the request. Setting the
        // config alone would therefore leave those routes reading and writing
        // a *different* session cookie than every other page on the same host:
        // two sessions in one browser, so a customer who logs in on /login is
        // anonymous everywhere else, and a CSRF token minted under one of them
        // is a 419 "Page Expired" under the other. Rename the store too.
        if (app()->resolved('session.store')) {
            app('session.store')->setName($name);
        }

        return $next($request);
    }

    /**
     * The session cookie name a host reads and writes: the app's base name
     * plus this host's own suffix.
     *
     * Idempotent, because a long-lived worker (Octane, a queue worker, or a
     * single test making several requests) runs more than one request in the
     * same booted app, where config('session.cookie') still carries the
     * previous request's suffix.
     *
     * Public because the name is only settled per request, so anything that
     * has to speak for a host's session outside a request — a test seeding a
     * session cookie, say — has to ask here rather than read
     * config('session.cookie'), which between requests is still the base name.
     */
    public static function nameFor(string $host, ?string $base = null): string
    {
        $base ??= (string) config('session.cookie');
        $suffix = '-'.Str::slug(str_replace('.', '-', $host));

        return str_ends_with($base, $suffix) ? $base : $base.$suffix;
    }
}
