<?php

return [
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
