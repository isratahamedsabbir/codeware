{{--
    Shared shell for every error page in resources/views/errors/.

    The layout is the Nicepage 404 template's: a large condensed status code
    beside a short line of copy and a round "back to the homepage" button, on
    plain white, stacking to a single centred column on a phone.

    Deliberately minimal in what it *depends on*. The status code, a sentence of
    static copy and a way back to the site — no branding lookup, no suggested
    pages, no database: an error page that has to query a database or read a
    cache store to render is one more thing that can fail exactly when the site
    is already broken, and there is nothing useful to say to a visitor who has
    just hit a 500 anyway.

    Still self-contained for the same reason: no @vite, no @include, no layout,
    inlined styles and a system font stack, so it renders even when the asset
    pipeline is what's down. That rules out the template's webfonts too — the
    condensed face below is only used if it happens to be installed locally, and
    falls back to a system stack otherwise.

    Usage:
        <x-errors.page :code="404" />
        <x-errors.page :code="404" title="..." message="..." />
--}}
@props([
    'code' => 500,
    'title' => null,
    'message' => null,
])

@php
    // The site's own home page, from APP_URL — not url('/'). The two differ the
    // moment the error is served on a panel host: the admin, vendor and delivery
    // portals each answer on their own subdomain, so url('/') would send a
    // visitor from a storefront 404 to the admin login page.
    $home = rtrim((string) config('app.url'), '/');

    // One pair of static strings per status, so every page says something a
    // visitor can act on without a lookup. A status with no entry of its own
    // (405, 409, 451, 502, …) gets the generic pair with the code dropped in.
    $copy = [
        400 => [__('Request could not be completed'), __('Something about this request was not valid. Check the address and try again.')],
        401 => [__('Unauthorized'), __('You need to sign in before you can view this page.')],
        403 => [__('Access denied'), __('You do not have permission to view this page. If you believe this is a mistake, ask an administrator to review your access.')],
        404 => [__('Sorry, page not found'), __('The page you requested could not be found.')],
        419 => [__('Your session has expired'), __('For your security the page expired because the tab was left open too long. Refresh the page and try again.')],
        429 => [__('Too many requests'), __('You have made too many requests in a short time. Please wait a moment and try again.')],
        500 => [__('Something went wrong'), __('An unexpected error occurred on our side. Our team has been notified — please try again in a moment.')],
        503 => [__('We will be back shortly'), __('We are carrying out scheduled maintenance and will be back online shortly. Thanks for your patience.')],
    ];

    [$defaultTitle, $defaultMessage] = $copy[$code] ?? [
        __('Error :code', ['code' => $code]),
        __('We hit an unexpected problem while loading this page. Please try again in a moment.'),
    ];

    $title ??= $defaultTitle;
    $message ??= $defaultMessage;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} &middot; {{ $title }}</title>

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            display: grid;
            place-items: center;
            margin: 0;
            padding: 2rem 1.5rem;
            background: #ffffff;
            color: #1a1a1a;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans Bengali", sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .err {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(1.5rem, 5vw, 4.5rem);
            width: 100%;
            max-width: 52rem;
        }

        .err-code {
            margin: 0;
            font-family: "Oswald", "Arial Narrow", "Helvetica Neue Condensed", ui-sans-serif, system-ui, sans-serif;
            font-size: clamp(5rem, 18vw, 11rem);
            font-weight: 500;
            line-height: 0.9;
            letter-spacing: -0.02em;
            color: #1a1a1a;
        }

        .err-copy { text-align: left; }

        .err-title {
            margin: 0;
            font-size: clamp(1.25rem, 3vw, 1.75rem);
            font-weight: 700;
            line-height: 1.25;
            color: #525252;
        }

        .err-message {
            margin: 0.75rem 0 0;
            font-size: 1rem;
            line-height: 1.6;
            color: #737373;
        }

        .err-btn {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.875rem 2.25rem;
            border-radius: 9999px;
            background: #737373;
            color: #ffffff;
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .err-btn:hover { background: #1a1a1a; }
        .err-btn:focus-visible { outline: 2px solid #1a1a1a; outline-offset: 3px; }

        @media (max-width: 640px) {
            .err { flex-direction: column; text-align: center; gap: 1.25rem; }
            .err-copy { text-align: center; }
        }

        /* No transform on :hover above — a filled animation outranks a regular
           declaration, so a lift would be silently dead while this is on. */
        @media (prefers-reduced-motion: no-preference) {
            .err > * { animation: err-rise 0.5s ease both; }
            .err-copy { animation-delay: 0.08s; }
        }

        @keyframes err-rise {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: none; }
        }

        @media (prefers-color-scheme: dark) {
            body { background: #0b0b0b; color: #f4f4f5; }
            .err-code { color: #fafafa; }
            .err-title { color: #d4d4d4; }
            .err-message { color: #a1a1a1; }
            .err-btn { background: #a1a1a1; color: #0b0b0b; }
            .err-btn:hover { background: #fafafa; }
            .err-btn:focus-visible { outline-color: #fafafa; }
        }
    </style>
</head>

<body>
    <main class="err">
        <h1 class="err-code">{{ $code }}</h1>

        <div class="err-copy">
            <p class="err-title">{{ $title }}</p>
            <p class="err-message">{{ $message }}</p>
            <a class="err-home err-btn" href="{{ $home }}">{{ __('Back to homepage') }}</a>
        </div>
    </main>
</body>
</html>
