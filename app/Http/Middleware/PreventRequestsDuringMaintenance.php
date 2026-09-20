<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as BasePreventRequestsDuringMaintenance;

/**
 * Host-aware maintenance guard — swapped in for the framework's
 * PreventRequestsDuringMaintenance in bootstrap/app.php. The path-based
 * `except[]` list can't express a host, and the admin panel now lives on its
 * own host (admin.codeware.test) rather than the `admin/*` path prefix, so
 * without this it would be cut off mid-maintenance — including the very
 * button that turns maintenance back off (Settings → Env). The vendor portal
 * is intentionally left out: only its `/login` path is reachable during
 * maintenance, exactly as before this panel moved onto its own host.
 */
class PreventRequestsDuringMaintenance extends BasePreventRequestsDuringMaintenance
{
    public function handle($request, Closure $next)
    {
        if ($request->getHost() === config('app.admin_host')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
