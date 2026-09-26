<?php

/*
 * Search-engine-facing behaviour that has to be decided in PHP rather than in a
 * view, because it applies to every response the site serves — not only the ones
 * a Blade template renders.
 *
 * `force_https` and `canonical_host_redirect` exist because a canonical URL is
 * only half the job: the site also has to actually *send* visitors to the address
 * it claims, or the http://, www and trailing-slash variants stay reachable and
 * keep splitting the page's signals. With SEO_SITE_URL configured these turn a
 * one-address site into a one-address site in practice, not just on paper.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | Switches every generated URL — canonicals, hreflang alternates, sitemaps,
    | og:url — to https, and (with canonical_host_redirect below) sends visitors
    | arriving over http to the https URL. Turn this on in production; it is off
    | by default so a local http:// install still works.
    |
    */

    'force_https' => (bool) env('SEO_FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Redirect to the canonical host
    |--------------------------------------------------------------------------
    |
    | 301s a request that does not match the configured site URL onto the one
    | that does, so http://example.com, https://www.example.com and
    | https://example.com/ all resolve to a single address. The admin, vendor and
    | delivery hosts are their own applications and are never touched — see
    | config('app.admin_host') and friends.
    |
    */

    'canonical_host_redirect' => (bool) env('SEO_CANONICAL_HOST_REDIRECT', true),

];
