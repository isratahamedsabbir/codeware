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

    $socialIcons = [
        'facebook' => ['abbr' => 'FB', 'label' => 'Facebook'],
        'twitter' => ['abbr' => 'X', 'label' => 'Twitter'],
        'instagram' => ['abbr' => 'IG', 'label' => 'Instagram'],
        'youtube' => ['abbr' => 'YT', 'label' => 'YouTube'],
        'linkedin' => ['abbr' => 'IN', 'label' => 'LinkedIn'],
        'tiktok' => ['abbr' => 'TT', 'label' => 'TikTok'],
    ];
    $socials = collect($socialIcons)
        ->map(fn ($meta, $platform) => ['url' => \App\Models\SocialLink::url($platform), 'abbr' => $meta['abbr'], 'label' => $meta['label']])
        ->filter(fn ($social) => filled($social['url']))
        ->values();
@endphp

@include('frontend.themes.portfolio.partials.header')

<main>
    <section class="pf-grid-bg relative flex min-h-[80vh] items-center overflow-hidden px-6 pt-24">
        <div class="mx-auto grid max-w-4xl items-center gap-10 text-center md:grid-cols-2 md:text-left">
            <h1 class="pf-gradient-text pf-mono text-8xl font-bold leading-none">404</h1>

            <div>
                <span class="pf-badge pf-mono mb-5 inline-flex rounded-full px-4 py-2 text-xs font-medium">
                    {{ __('Error') }} &middot; {{ __('Not found') }}
                </span>
                <h2 class="pf-heading text-2xl font-bold sm:text-3xl">{{ __('Sorry, page not found') }}</h2>
                <p class="mt-3 text-(--pf-text-muted)">{{ __('This page does not exist. The one-pager below is all there is — try one of its sections.') }}</p>

                <a href="{{ url('/') }}" class="pf-btn-solid pf-mono mt-8 inline-flex items-center gap-2 rounded-full px-6 py-3 text-xs font-semibold uppercase tracking-widest">
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
