{{--
    The portfolio theme's 404.

    Themes::errorView() prefers a theme's own errors/{code}.blade.php over the
    shared resources/views/errors/{code}.blade.php, so this file is what a
    visitor on the portfolio theme actually gets. A theme that ships no such file
    falls back to the shared page instead — nothing here is required for the
    storefront to work.

    Built from the same pieces as the one-pager: the theme-portfolio body class,
    its own public/themes/portfolio/style.css, the pf-* components and the
    --pf-* custom properties. That matters more here than in the other themes —
    the portfolio has a light/dark toggle, and the page has to follow it, which
    it does for free by reading the same variables the rest of the theme does
    rather than hardcoding a background.

    The header and footer are handed the same $siteName / $siteIcon / $socials
    the one-pager builds for itself, because those partials expect them from the
    including view (see Portfolio theme partials).

    This page is also the portfolio's answer to a dead URL being a dead end: its
    nav items are section anchors, so the header still walks a visitor back
    through the one-pager.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('themes/portfolio/style.css') }}">
    {{-- A 404 must never be indexed, and must never claim a canonical URL for a
         page that does not exist — so partials.seo-meta is deliberately absent
         here (it derives both from $page). --}}
    <meta name="robots" content="noindex, nofollow">
    <style>[x-cloak]{display:none!important}</style>
    @include('partials.custom-code-head')
</head>
<body class="theme-portfolio antialiased">

@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');

    $platformLabels = [
        'facebook' => 'Facebook',
        'twitter' => 'X',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'tiktok' => 'TikTok',
        'github' => 'GitHub',
        'gitlab' => 'GitLab',
        'behance' => 'Behance',
        'dribbble' => 'Dribbble',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
    ];
    $socials = collect(array_keys($platformLabels))
        ->map(fn (string $platform) => [
            'platform' => $platform,
            'label' => $platformLabels[$platform],
            'url' => \App\Models\SocialLink::url($platform),
        ])
        ->filter(fn (array $social) => filled($social['url']))
        ->sortBy(fn (array $social) => array_search($social['platform'], array_keys($platformLabels)))
        ->values();
@endphp

@include('frontend.themes.portfolio.partials.header')

<main>
    <section class="pf-grid-bg relative flex min-h-[80vh] items-center overflow-hidden px-6 pt-32 pb-20">
        <div class="pf-glow pointer-events-none absolute top-0 left-1/2 h-105 w-105 -translate-x-1/2 rounded-full opacity-50" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-4xl items-center gap-12 md:grid-cols-2">
            <h1 class="pf-gradient-text pf-mono text-8xl leading-none font-bold sm:text-9xl">404</h1>

            <div>
                <span class="pf-badge pf-mono mb-6 inline-flex px-4 py-2 text-[11px] font-medium tracking-wide">
                    {{ __('Error') }} &middot; {{ __('Not found') }}
                </span>
                <h2 class="pf-heading text-2xl font-bold sm:text-3xl">{{ __('Sorry, page not found') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-(--pf-text-muted)">{{ __('This page does not exist. The sections below are all there is — try one of them.') }}</p>

                <a href="{{ url('/') }}" class="pf-btn-solid pf-mono mt-8 px-7 py-3 text-xs tracking-wider uppercase">
                    {{ __('Back to homepage') }}
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
</main>

@include('frontend.themes.portfolio.partials.footer')

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>
