{{--
    Shared shell for every error page in resources/views/errors/.

    Deliberately standalone — no @vite, no @include, no layout. A 500 is very
    often a dead database or a broken cache store, and an error page that also
    fails to render is the worst possible outcome, so the styles are inlined,
    the font is a system stack (no external request to hang on), and every
    optional lookup (site name, logo, favicon, brand colour) sits behind a
    try/catch with a hardcoded fallback.

    Usage:
        <x-errors.page :code="404" :title="__('Page not found')"
            :message="__('...')"
            :links="[['label' => 'Shop', 'href' => route('shop')]]" />
--}}
@props([
    'code' => 500,
    'title',
    'message' => null,
    'back' => true,
    'links' => [],
    'reference' => null,
    'brand' => null,
])

@php
    // Optional, best-effort branding. Each lookup can throw on a broken DB or
    // cache store, which is a common enough cause of the error being rendered.
    $siteName = config('app.name', 'Codeware');
    $favicon = '/favicon/favicon.ico';
    $logo = asset('default/logo.png');

    try {
        $siteName = \App\Models\Setting::get('site_name') ?: $siteName;
        $favicon = \App\Models\Setting::get('favicon') ?: $favicon;
        $logo = \App\Models\Setting::get('site_icon') ?: $logo;
    } catch (\Throwable) {
        // Keep the defaults.
    }

    // A theme's own error page passes its accent through, so a themed 404 reads
    // as that theme (see frontend/themes/*/errors/404.blade.php); without one we
    // fall back to the ecommerce brand colours, and failing that to a hardcoded
    // accent. Deliberately still a Setting read, never a themed partial: this
    // shell has to render when the theme's own views are what's broken.
    if (! is_string($brand) || ! preg_match('/^#[0-9a-fA-F]{3,8}$/', trim($brand))) {
        $brand = null;

        try {
            $hex = \App\Models\Setting::get('theme_ecommerce_accent_color')
                ?: \App\Models\Setting::get('theme_ecommerce_primary_color');
            if (is_string($hex) && preg_match('/^#[0-9a-fA-F]{3,8}$/', trim($hex))) {
                $brand = trim($hex);
            }
        } catch (\Throwable) {
            // Keep the default accent.
        }
    }

    $brand = $brand ?: '#045b30';

    $links = array_values(array_filter($links, fn ($l) => filled($l['href'] ?? null)));
    $isServerError = (int) $code >= 500;

    // One glyph per family of error, so the page reads at a glance.
    $icon = match ((int) $code) {
        401 => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
        403 => 'M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636',
        404 => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM13.5 10.5h-6',
        419, 429 => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        503 => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z',
        default => $isServerError
            ? 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'
            : 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="{{ $brand }}">
    <title>{{ $code }} · {{ $title }} — {{ $siteName }}</title>

    <link rel="icon" href="{{ $favicon }}" sizes="any">

    <style>
        :root {
            --err-brand: {{ $brand }};
            --err-brand-soft: color-mix(in srgb, var(--err-brand) 10%, transparent);
            --err-brand-line: color-mix(in srgb, var(--err-brand) 22%, transparent);
            --err-bg: #f8f9fa;
            --err-card: #ffffff;
            --err-fg: #0f172a;
            --err-muted: #64748b;
            --err-line: #e2e8f0;
            --err-dot: #d8dee6;
            --err-shadow: 0 1px 2px rgb(15 23 42 / 0.04), 0 12px 32px -12px rgb(15 23 42 / 0.12);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --err-brand-soft: color-mix(in srgb, var(--err-brand) 18%, transparent);
                --err-brand-line: color-mix(in srgb, var(--err-brand) 35%, transparent);
                --err-bg: #09090b;
                --err-card: #111114;
                --err-fg: #f4f4f5;
                --err-muted: #a1a1aa;
                --err-line: #27272a;
                --err-dot: #1f1f23;
                --err-shadow: 0 1px 2px rgb(0 0 0 / 0.4), 0 16px 40px -16px rgb(0 0 0 / 0.6);
            }
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            background: var(--err-bg);
            color: var(--err-fg);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans Bengali", sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Faint dot grid, faded out towards the edges, plus a soft brand glow. */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            background-image: radial-gradient(var(--err-dot) 1px, transparent 1px);
            background-size: 22px 22px;
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 30%, transparent 75%);
            mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 30%, transparent 75%);
        }

        body::after {
            content: "";
            position: fixed;
            top: -18rem;
            left: 50%;
            z-index: -1;
            width: 42rem;
            height: 32rem;
            transform: translateX(-50%);
            background: radial-gradient(closest-side, var(--err-brand-soft), transparent);
            pointer-events: none;
        }

        .err-shell {
            display: flex;
            flex-direction: column;
            min-height: 100dvh;
            padding: 0 1.25rem;
        }

        .err-header,
        .err-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            width: 100%;
            max-width: 64rem;
            margin: 0 auto;
        }

        .err-header { padding: 1.5rem 0; }

        .err-logo { display: inline-flex; border-radius: 6px; }
        .err-logo img { display: block; height: 2.25rem; width: auto; }
        .err-logo:focus-visible { outline: 2px solid var(--err-brand); outline-offset: 4px; }

        .err-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--err-muted);
        }

        .err-status-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 999px;
            background: {{ $isServerError ? '#ef4444' : '#f59e0b' }};
            box-shadow: 0 0 0 3px {{ $isServerError ? 'rgb(239 68 68 / 0.18)' : 'rgb(245 158 11 / 0.18)' }};
        }

        .err-main {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 1.5rem 0 3rem;
        }

        .err-card {
            position: relative;
            width: 100%;
            max-width: 30rem;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            background: var(--err-card);
            border: 1px solid var(--err-line);
            border-radius: 16px;
            box-shadow: var(--err-shadow);
            animation: err-rise 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) both;
        }

        @keyframes err-rise {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: none; }
        }

        .err-icon {
            display: grid;
            place-items: center;
            width: 3.5rem;
            height: 3.5rem;
            margin: 0 auto;
            color: var(--err-brand);
            background: var(--err-brand-soft);
            border: 1px solid var(--err-brand-line);
            border-radius: 14px;
        }

        .err-icon svg { width: 1.625rem; height: 1.625rem; }

        .err-code {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            margin: 1.5rem 0 0;
            padding: 0.25rem 0.625rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--err-brand);
            background: var(--err-brand-soft);
            border-radius: 999px;
        }

        .err-title {
            margin: 0.875rem 0 0;
            font-size: clamp(1.5rem, 5vw, 1.875rem);
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            text-wrap: balance;
        }

        .err-message {
            margin: 0.75rem auto 0;
            max-width: 25rem;
            font-size: 0.9375rem;
            line-height: 1.65;
            color: var(--err-muted);
            text-wrap: pretty;
        }

        /* Optional extra note supplied by an error view via the slot. */
        .err-note {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            margin-top: 1.25rem;
            padding: 0.4375rem 0.875rem;
            font-size: 0.8125rem;
            color: var(--err-muted);
            background: var(--err-bg);
            border: 1px solid var(--err-line);
            border-radius: 999px;
        }

        .err-note code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-weight: 600;
            color: var(--err-fg);
        }

        .err-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.625rem;
            margin-top: 2rem;
        }

        .err-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2.625rem;
            padding: 0.625rem 1.125rem;
            font: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.25rem;
            text-decoration: none;
            border-radius: 10px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: filter 0.15s ease, background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        }

        .err-btn:active { transform: translateY(1px); }

        .err-btn:focus-visible {
            outline: 2px solid var(--err-brand);
            outline-offset: 2px;
        }

        .err-btn--primary {
            background: var(--err-brand);
            color: #fff;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.12), inset 0 1px 0 rgb(255 255 255 / 0.12);
        }
        .err-btn--primary:hover { filter: brightness(1.08); }

        .err-btn--ghost {
            background: var(--err-card);
            color: var(--err-fg);
            border-color: var(--err-line);
        }
        .err-btn--ghost:hover { background: var(--err-bg); border-color: var(--err-muted); }

        .err-btn svg { width: 1rem; height: 1rem; flex: none; }

        .err-links {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--err-line);
            text-align: left;
        }

        .err-links-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--err-muted);
        }

        .err-links ul { margin: 0; padding: 0; list-style: none; }

        .err-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.625rem 0.75rem;
            margin: 0 -0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--err-fg);
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .err-link svg {
            width: 1rem;
            height: 1rem;
            color: var(--err-muted);
            transition: transform 0.15s ease, color 0.15s ease;
        }

        .err-link:hover { background: var(--err-bg); color: var(--err-brand); }
        .err-link:hover svg { color: var(--err-brand); transform: translateX(3px); }
        .err-link:focus-visible { outline: 2px solid var(--err-brand); outline-offset: 0; }

        .err-reference {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 1.75rem 0 0;
            font-size: 0.75rem;
            color: var(--err-muted);
        }

        .err-reference code {
            padding: 0.1875rem 0.5rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: var(--err-fg);
            background: var(--err-bg);
            border: 1px solid var(--err-line);
            border-radius: 6px;
        }

        .err-footer {
            padding: 1.5rem 0;
            border-top: 1px solid var(--err-line);
            font-size: 0.75rem;
            color: var(--err-muted);
        }

        .err-footer a { color: inherit; text-decoration: none; }
        .err-footer a:hover { color: var(--err-fg); }

        @media (max-width: 480px) {
            .err-card { padding: 2rem 1.25rem 1.5rem; border-radius: 14px; }
            .err-actions { flex-direction: column; }
            .err-btn { width: 100%; }
            .err-footer { flex-direction: column; justify-content: center; text-align: center; gap: 0.25rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .err-card { animation: none; }
            .err-btn, .err-link, .err-link svg { transition: none; }
        }
    </style>
</head>

<body>
    <div class="err-shell">
        <header class="err-header">
            <a href="{{ url('/') }}" class="err-logo" aria-label="{{ $siteName }}">
                <img src="{{ $logo }}" alt="{{ $siteName }}">
            </a>

            <span class="err-status">
                <span class="err-status-dot" aria-hidden="true"></span>
                HTTP {{ $code }}
            </span>
        </header>

        <main class="err-main">
            <section class="err-card" aria-labelledby="err-title">
                <div class="err-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                    </svg>
                </div>

                <p class="err-code">{{ __('Error') }} {{ $code }}</p>
                <h1 class="err-title" id="err-title">{{ $title }}</h1>

                @if ($message)
                    <p class="err-message">{{ $message }}</p>
                @endif

                @if (trim((string) $slot))
                    <div>{{ $slot }}</div>
                @endif

                <div class="err-actions">
                    <a href="{{ url('/') }}" class="err-btn err-btn--primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 11.204 3.045c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                        </svg>
                        {{ __('Back to homepage') }}
                    </a>

                    @if ($back)
                        <button type="button" class="err-btn err-btn--ghost" onclick="history.back()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                            </svg>
                            {{ __('Go back') }}
                        </button>
                    @endif
                </div>

                @if ($links)
                    <nav class="err-links" aria-label="{{ __('Suggested pages') }}">
                        <span class="err-links-label">{{ __('Suggested pages') }}</span>
                        <ul>
                            @foreach ($links as $link)
                                <li>
                                    <a href="{{ $link['href'] }}" class="err-link">
                                        {{ $link['label'] }}
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @if ($reference)
                    <p class="err-reference">
                        {{ __('Error reference') }} <code>{{ $reference }}</code>
                    </p>
                @endif
            </section>
        </main>

        <footer class="err-footer">
            <span>&copy; {{ date('Y') }} {{ $siteName }}</span>
            <a href="{{ url('/') }}">{{ parse_url(url('/'), PHP_URL_HOST) }}</a>
        </footer>
    </div>
</body>
</html>
