<?php

namespace App\Http\Middleware;

use App\Support\Themes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a theme's routes from answering on another theme's site.
 *
 * routes/web.php registers every theme's routes/web/{slug}.php behind this
 * middleware rather than registering only the active one. That is deliberate:
 *
 *  - A theme's file is the whole description of that theme's site, so it is worth
 *    registering in full, but the site you get is the active theme's.
 *  - Deciding at boot instead would freeze the choice: the route table is built
 *    once, so switching theme would need the cache dropped and a rebuild to take
 *    effect, and the table would depend on a settings row at boot.
 *  - The ecommerce 404 and header call route('shop'), which only resolves
 *    because every theme's routes exist.
 *
 * So every theme's routes are registered and this middleware decides, per
 * request, whether the active theme can serve the page that was matched. The
 * check is the same one Themes::view() makes when it looks for a template, just
 * made before the controller runs — so /shop on a portfolio site is a 404 from
 * the portfolio's own error page rather than a shop page.
 *
 * Keyed on the matched route's *name*, not on the file it came from: a name a
 * theme shares with another (home, page) must stay reachable on both, while a
 * name only the ecommerce theme uses must not. Names that aren't pages with a
 * theme template behind them at all — the unnamed /home aliases, the click
 * tracker — pass through, and the controller decides.
 */
class EnsureActiveTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        abort_if(
            $name !== null && ! Themes::activeThemeCanRender($name),
            404,
            'This page is not part of the active theme.'
        );

        return $next($request);
    }
}
