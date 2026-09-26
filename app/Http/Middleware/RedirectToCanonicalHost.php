<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\Seo\Url;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends visitors to the one address the site says it lives at.
 *
 * A canonical URL is a claim, and a claim the site doesn't act on is worth
 * little: if https://example.com/, http://example.com/ and
 * https://www.example.com/ all render 200 with the same canonical, the crawler
 * keeps meeting three URLs and the ranking signal is split three ways. This 301s
 * the variants onto the configured one, which is what makes Seo\Url's single
 * spelling of a URL true of the whole site and not just of its <link> tags.
 *
 * Inert until an admin fills in the `seo_site_url` setting: with it blank there
 * is no address to redirect to, and a local http:// install has to keep working.
 * The query string is carried across the redirect on purpose — a visitor who
 * arrived from a campaign link should still land on the campaign page, just over
 * https. (It is still stripped from the canonical itself; see Seo\Url::path.)
 *
 * Both sides of the "are we already there?" comparison are normalised rather
 * than compared as written. A request for the site root arrives spelled both
 * `https://example.com` and `https://example.com/`, and a raw comparison of
 * those two sends every visitor to the site homepage in a redirect loop that
 * never settles on an answer.
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldRedirect($request)) {
            return $next($request);
        }

        // Url::url() is the one place a full storefront URL gets built, so the
        // address the visitor is sent to and the canonical the page then
        // advertises come out of the same code and cannot drift apart.
        $target = Url::url($request->path())
            .($request->getQueryString() ? '?'.$request->getQueryString() : '');

        if (Url::normalize($target) === Url::normalize($request->fullUrl())) {
            return $next($request);
        }

        return redirect()->away($target, 301);
    }

    private function shouldRedirect(Request $request): bool
    {
        if (! config('seo.canonical_host_redirect') || ! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        if (trim((string) Setting::get('seo_site_url', '')) === '') {
            return false;
        }

        // Only the public storefront is this site's canonical address. The admin
        // panel, the vendor portal and the delivery portal are separate
        // applications on their own hosts (see config/app.php) — each with its
        // own login and its own cookie scope — so redirecting them onto the
        // storefront host would break them outright.
        return ! in_array($request->getHost(), $this->portalHosts(), true);
    }

    /**
     * @return array<int, string>
     */
    private function portalHosts(): array
    {
        return array_values(array_filter([
            config('app.admin_host'),
            config('app.vendor_host'),
            config('app.delivery_host'),
        ]));
    }
}
