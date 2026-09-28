<?php

namespace App\Http\Middleware;

use App\Support\Referral;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records the referrer behind a `?ref=USR-XXXXXXXX` link against the visitor's
 * session, so the order they go on to place remembers who sent them.
 *
 * Registered on the themed storefront routes (see routes/web.php), which is
 * wider than it looks on purpose: the ref is captured on any storefront page
 * rather than only the product page the share row lives on, because a link is
 * very often opened, browsed and only then bought — and it is the order, not the
 * page it was opened from, that the attribution belongs to. A theme with no cart
 * simply never reads it (see App\Support\Referral).
 *
 * The work is one optional query string read and, only for a link that resolves
 * to somebody other than the visitor, one indexed lookup. Anything unrecognised
 * is dropped by Referral::capture() rather than rejected here, so a bad ref can
 * never turn a shared link into a broken page.
 */
class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        Referral::capture($request->query(Referral::QUERY_KEY));

        return $next($request);
    }
}
