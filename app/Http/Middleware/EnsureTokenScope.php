<?php

namespace App\Http\Middleware;

use App\Support\PuckEditor;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the abilities on a Sanctum token mean something on the admin API.
 *
 * The admin routes gate on `can:access-admin`, a Gate over the user's roles, so
 * on its own every valid token reaches the whole API — including a Puck editor
 * token that was minted with the narrow `puck:edit` ability, and which travels in
 * a URL to a separate host. A leak of that token would otherwise be a leak of the
 * admin's full API access for its lifetime.
 *
 * A token that carries `puck:edit` and not the wildcard may therefore only call
 * the endpoints the visual editor actually uses (content, products, media and the
 * layout). Users, settings, contacts and subscribers answer 403 to it. Tokens with
 * the wildcard, and session-authenticated (SPA/transient) requests, are unchanged.
 */
class EnsureTokenScope
{
    /** Route-name prefixes (under `api.v1.admin.`) a Puck editor token may call. */
    private const PUCK_ALLOWED = [
        'posts.', 'pages.', 'products.', 'product-categories.', 'media.', 'layout.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return $next($request);
        }

        $abilities = (array) $token->abilities;

        if (in_array('*', $abilities, true) || ! in_array(PuckEditor::ABILITY, $abilities, true)) {
            return $next($request);
        }

        $name = (string) $request->route()?->getName();
        $name = str_starts_with($name, 'api.v1.admin.') ? substr($name, strlen('api.v1.admin.')) : '';

        foreach (self::PUCK_ALLOWED as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return $next($request);
            }
        }

        abort(403, 'This token is limited to the visual editor.');
    }
}
