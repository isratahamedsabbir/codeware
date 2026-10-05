<?php

/*
|--------------------------------------------------------------------------
| Passkeys (WebAuthn)
|--------------------------------------------------------------------------
|
| laravel/passkeys, wired in through Fortify's Features::passkeys(). Fortify
| registers the routes with no domain, so they answer on every host the app
| serves — the main site, the admin panel, the vendor portal and the delivery
| portal — which is what makes one enrolment usable from all of them.
|
| The two values below are the ones that actually have to be right, and both
| are host lists rather than single values because of that. A passkey is bound
| to the *relying party id*, and WebAuthn only accepts an RP id that is the
| page's own host or a suffix of it — so an id of "codeware.test" covers
| admin.codeware.test, vendor.codeware.test and deliveryboy.codeware.test at
| once, which is why it is deliberately not APP_URL's host.
|
| allowed_origins is the exact set of origins a ceremony may complete on, and
| the browser reports one of them: a passkey enrolled on the admin host will not
| verify on the storefront unless both are listed here.
|
*/

// The vendor and delivery portals are configured as hosts (config('app.
// vendor_host'), derived from VENDOR_URL) rather than as full URLs, so their
// origins are assembled below from APP_URL's scheme. Inline rather than in a
// named function because a config file is plain PHP and Pest boots a fresh
// application per test — a function declared here would be redeclared.
$scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

$portalOrigin = function (?string $host) use ($scheme): ?string {
    if (blank($host)) {
        return null;
    }

    // Already a full origin — nothing to assemble.
    if (str_contains($host, '://')) {
        return rtrim($host, '/');
    }

    return $scheme.'://'.$host;
};

return [

    /*
     | The common ancestor of every host this app serves, NOT any one of them.
     |
     | WebAuthn accepts an RP id that is the page's host or a suffix of it, so
     | "codeware.test" is what lets a single enrolment answer on the storefront,
     | on admin.codeware.test, on the vendor host and on the delivery host. Naming
     | one of the panels instead — which the admin host would suggest — is the
     | failure this whole config exists to avoid: the other three origins would
     | then be suffix violations and every ceremony on them would be rejected.
     |
     | Derived from APP_URL's host on purpose. The panel hosts are configured as
     | hosts derived from their own URLs (config('app.admin_host') and friends),
     | so APP_URL is the only one of the four guaranteed to be the shared parent
     | rather than a sibling.
     */
    'relying_party_id' => env('PASSKEYS_RP_ID')
        ?: parse_url((string) config('app.url'), PHP_URL_HOST),

    'allowed_origins' => array_values(array_unique(array_filter([
        config('app.url'),
        config('app.admin_url'),
        config('app.frontend_url'),
        $portalOrigin(config('app.vendor_host')),
        $portalOrigin(config('app.delivery_host')),
    ]))),

    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),

    'timeout' => 60000,

    'guard' => 'web',

    'middleware' => ['web'],

    /*
     | Throttling of the ceremony routes is not configured here: the named
     | 'passkeys' limiter (FortifyServiceProvider::configureRateLimiting) is what
     | config/fortify.php's Features::passkeys() points them at, and a package
     | version that grows its own throttle key should be matched there, not by
     | guessing a key name this release does not read.
     |
     | Fortify's own PasskeyLoginResponse reads this. Left as the site root on
     | purpose: the routes are domainless, so "/" is the admin panel's dashboard
     | on the admin host, the vendor dashboard on the vendor host and the
     | storefront on the main one — one value, correct on every host, rather
     | than an APP_URL that would drop a passkey login into the wrong panel.
     */
    'redirect' => '/',

];
