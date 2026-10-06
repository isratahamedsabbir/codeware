<?php

return [
    // Anything that puts PHP on the server from the admin panel (plugin/theme
    // zip install, plugin/theme scaffolding). Off by default in production:
    // deploy code through the pipeline instead. Set ALLOW_PLUGIN_UPLOAD=true to
    // opt in; installs then also need the admin's password, are logged and
    // alert every admin, and (if PLUGIN_ALLOWED_SHA256 lists comma-separated
    // sha256 digests) only accept packages whose zip matches one of them.
    'code_install' => [
        'allowed' => filter_var(env('ALLOW_PLUGIN_UPLOAD', in_array(env('APP_ENV'), ['local', 'testing'], true)), FILTER_VALIDATE_BOOLEAN),
        'sha256' => array_values(array_filter(array_map('trim', explode(',', (string) env('PLUGIN_ALLOWED_SHA256', ''))))),
    ],

    // Admin-role accounts must have a second factor to use the panel. On by
    // default in production; EnsureMfaEnforced sends them to enrolment.
    'require_admin_mfa' => filter_var(env('REQUIRE_ADMIN_MFA', ! in_array(env('APP_ENV'), ['local', 'testing'], true)), FILTER_VALIDATE_BOOLEAN),

    // APP_DEBUG can't be switched on from Admin -> Env in production (it exposes
    // stack traces and secrets). Set ALLOW_DEBUG_IN_PRODUCTION=true to lift it.
    'allow_debug_in_production' => filter_var(env('ALLOW_DEBUG_IN_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),

    'headers' => [
        // Sent in every environment except HSTS, which is production-only.
        'hsts' => env('SECURITY_HSTS', 'max-age=31536000; includeSubDomains'),
        'permissions_policy' => 'camera=(), microphone=(), geolocation=()',
    ],

    'csp' => [
        'enabled' => env('CSP_ENABLED', true),

        // Start in report-only: browsers log violations to the console (and to
        // CSP_REPORT_URI if set) without blocking. Fix what it flags, then set
        // CSP_REPORT_ONLY=false to enforce.
        'report_only' => env('CSP_REPORT_ONLY', true),
        'report_uri' => env('CSP_REPORT_URI'),

        // Third-party origins the pages legitimately load from.
        'script_src' => [
            'https://cdnjs.cloudflare.com',
            'https://cdn.jsdelivr.net',
            'https://www.google.com',
            'https://www.gstatic.com',
            'https://maps.googleapis.com',
            'https://challenges.cloudflare.com',
        ],
        'style_src' => [
            'https://cdnjs.cloudflare.com',
            'https://fonts.googleapis.com',
            'https://fonts.bunny.net',
        ],
        'font_src' => [
            'https://cdnjs.cloudflare.com',
            'https://fonts.gstatic.com',
            'https://fonts.bunny.net',
        ],
        'connect_src' => [
            'https://maps.googleapis.com',
        ],
        'frame_src' => [
            'https://www.google.com',
            'https://challenges.cloudflare.com',
        ],
    ],
];
