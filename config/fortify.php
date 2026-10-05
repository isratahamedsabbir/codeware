<?php

use Laravel\Fortify\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Fortify will use while
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Fortify Password Broker
    |--------------------------------------------------------------------------
    |
    | Here you may specify which password broker Fortify can use when a user
    | is resetting their password. This configured value should match one
    | of your password brokers setup in your "auth" configuration file.
    |
    */

    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    |
    | This value defines which model attribute should be considered as your
    | application's "username" field. Typically, this might be the email
    | address of the users but you are free to change this value here.
    |
    | Out of the box, Fortify expects forgot password and reset password
    | requests to have a field named 'email'. If the application uses
    | another name for the field you may define it below as needed.
    |
    */

    'username' => 'email',

    'email' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Lowercase Usernames
    |--------------------------------------------------------------------------
    |
    | This value defines whether usernames should be lowercased before saving
    | them in the database, as some database system string fields are case
    | sensitive. You may disable this for your application if necessary.
    |
    */

    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    |
    | Here you may configure the path where users will get redirected during
    | authentication or password reset when the operations are successful
    | and the user is authenticated. You are free to change this value.
    |
    */

    'home' => '/dashboard',

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    |
    | Here you may specify which prefix Fortify will assign to all the routes
    | that it registers with the application. If necessary, you may change
    | subdomain under which all of the Fortify routes will be available.
    |
    */

    'prefix' => '',

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Here you may specify which middleware Fortify will assign to the routes
    | that it registers with the application. If necessary, you may change
    | these middleware but typically this provided default is preferred.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | By default, Fortify will throttle logins to five requests per minute for
    | every email and IP address combination. However, if you would like to
    | specify a custom rate limiter to call then you may specify it here.
    |
    */

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        // Registered in App\Providers\FortifyServiceProvider::configureRateLimiting().
        // Read here by Fortify's own passkey routes; left unset it is null,
        // which silently means *no* throttle on a credential-guessing endpoint.
        'passkeys' => 'passkeys',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    |
    | Here you may specify if the routes returning views should be disabled as
    | you may not need them when building your own application. This may be
    | especially true if you're writing a custom single-page application.
    |
    */

    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Some of the Fortify features are optional. You may disable the features
    | by removing them from this array. You're free to only remove some of
    | these features, or you can even remove all of these if you need to.
    |
    */

    'features' => [
        Features::registration(),

        // Deliberately absent: Features::resetPasswords(). Password resets here
        // are by emailed code, not by the token link Fortify's feature registers
        // — routes/web.php keeps the same route names under App\Http\Controllers\
        // Auth\PasswordResetOtpController instead, so the flow is unchanged from
        // the outside while the link no longer depends on a page elsewhere to
        // resolve. See App\Services\PasswordResetService.

        Features::emailVerification(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
            // 'window' => 0
        ]),

        // Passkeys (WebAuthn) — the second kind of second factor, interchangeable
        // with TOTP everywhere a challenge is answered: App\Support\Mfa treats
        // either one as "this account has a factor", and the challenge page
        // offers whichever the account actually has.
        //
        // The routes Fortify registers for these are domainless, so they answer
        // on the main site, the admin panel, the vendor portal and the delivery
        // portal alike — which is what lets one enrolment serve all four. That
        // only holds if config/passkeys.php lists every host's origin, because
        // WebAuthn refuses a ceremony completed on an origin it doesn't know.
        Features::passkeys([
            'confirmPassword' => true,
        ]),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passkey Relying Party
    |--------------------------------------------------------------------------
    |
    | Fortify's service provider copies `fortify.passkeys.*` over `passkeys.*` on
    | boot, so anything set only in config/passkeys.php is overwritten with the
    | package defaults (allowed_origins = [APP_URL] alone) and a passkey created
    | on admin.codeware.test is refused. These are the real values;
    | config/passkeys.php reads them back from here.
    |
    | The relying party id is the common ancestor of every host (APP_URL's host,
    | e.g. codeware.test), not one panel's host, so one enrolment answers on the
    | storefront, admin, vendor and delivery hosts alike. allowed_origins is the
    | exact set of origins a ceremony may complete on.
    */
    'passkeys' => [
        // PASSKEYS_RP_ID wins when set. Otherwise it is the longest domain every
        // configured host (APP_URL, ADMIN_URL, the vendor and delivery hosts)
        // shares, because WebAuthn accepts an RP id only if it is the page's own
        // host or a suffix of it. Taking APP_URL's host alone breaks as soon as
        // the panel lives on a different domain than APP_URL (the browser then
        // reports "the relying party ID is not a registrable domain suffix").
        // If the hosts share nothing usable, fall back to the admin host so at
        // least that panel works.
        'relying_party_id' => env('PASSKEYS_RP_ID') ?: (function () {
            $hosts = array_values(array_filter(array_map(
                fn ($url) => blank($url) ? null : strtolower((string) (parse_url(str_contains($url, '://') ? $url : 'https://'.$url, PHP_URL_HOST) ?: '')),
                [config('app.url'), config('app.admin_url'), config('app.vendor_host'), config('app.delivery_host')],
            )));

            $common = null;

            foreach ($hosts as $host) {
                $labels = array_reverse(explode('.', $host));

                if ($common === null) {
                    $common = $labels;

                    continue;
                }

                $shared = [];

                foreach ($common as $i => $label) {
                    if (($labels[$i] ?? null) !== $label) {
                        break;
                    }

                    $shared[] = $label;
                }

                $common = $shared;
            }

            if ($common !== null && count($common) >= 2) {
                return implode('.', array_reverse($common));
            }

            return parse_url((string) config('app.admin_url'), PHP_URL_HOST)
                ?: parse_url((string) config('app.url'), PHP_URL_HOST);
        })(),

        'allowed_origins' => array_values(array_unique(array_filter(array_map(
            // The vendor and delivery portals are configured as bare hosts; every
            // other entry is already a full origin.
            fn ($origin) => blank($origin) ? null : (str_contains($origin, '://')
                ? rtrim($origin, '/')
                : (parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https').'://'.$origin),
            [
                config('app.url'),
                config('app.admin_url'),
                config('app.frontend_url'),
                config('app.vendor_host'),
                config('app.delivery_host'),
            ],
        )))),
    ],

];
