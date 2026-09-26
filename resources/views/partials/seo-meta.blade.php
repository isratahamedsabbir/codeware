{{--
    Everything a page says about itself to a search engine.

    A thin printer over App\Support\Seo\SeoResolver: the fallback chain
    (per-page field -> global "Global SEO" setting -> default) and every decision
    about canonical URLs, robots directives, og:type and hreflang alternates
    lives in SeoResolver, so this file only decides how the answers are written
    out. Both partials.head (for the <title>) and this partial resolve the same
    request, and SeoResolver memoises per request so they always agree.

    A route that renders no themed template has no Page and no RouteSeo profile,
    so it gets the neutral defaults here — which is the right answer for the
    signed invoice and voucher links, whose views are the same on every theme.
--}}
@php
    $seo = \App\Support\Seo\SeoResolver::resolve(request(), $page ?? null, $title ?? null);
    $robots = $seo->robotsContent();

    // schema.org structured data: the same page described as *what it is*
    // rather than what it is called, which is the only format a product price
    // or an article date can be read out of. Built from the same SeoData as
    // everything below, so the two can never describe different pages, and
    // skipped entirely on a noindex page where it could not be read anyway.
    $document = \App\Support\Seo\Schema::document($seo, $page ?? null);
@endphp

@if ($robots)
    <meta name="robots" content="{{ $robots }}">
@endif

@if ($seo->description)
    <meta name="description" content="{{ $seo->description }}">
@endif

<link rel="canonical" href="{{ $seo->canonical }}">

{{--
    hreflang alternates, one per active language, plus x-default. Only worth
    emitting on a page that is genuinely translated into more than one language —
    a single-locale site should not tell a crawler these are all translations of
    each other. SeoResolver decides; see LocalizedUrl for why a real per-locale
    URL is required before these mean anything.
--}}
@foreach ($seo->alternates as $alternate)
    <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}">
@endforeach

<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:url" content="{{ $seo->ogUrl }}">
<meta property="og:site_name" content="{{ $seo->siteName }}">
<meta property="og:locale" content="{{ str_replace('-', '_', $seo->locale) }}">
@if ($seo->ogTitle)
    <meta property="og:title" content="{{ $seo->ogTitle }}">
@endif
@if ($seo->ogDescription)
    <meta property="og:description" content="{{ $seo->ogDescription }}">
@endif
@if ($seo->ogImage)
    <meta property="og:image" content="{{ $seo->ogImage }}">
    <meta property="og:image:alt" content="{{ $seo->ogImageAlt }}">
@endif

<meta name="twitter:card" content="{{ $seo->twitterCard }}">
@if ($seo->twitterSite)
    <meta name="twitter:site" content="{{ $seo->twitterSite }}">
@endif
@if ($seo->twitterTitle)
    <meta name="twitter:title" content="{{ $seo->twitterTitle }}">
@endif
@if ($seo->twitterDescription)
    <meta name="twitter:description" content="{{ $seo->twitterDescription }}">
@endif
@if ($seo->twitterImage)
    <meta name="twitter:image" content="{{ $seo->twitterImage }}">
@endif

@if ($document)
    <script type="application/ld+json">{!! $document !!}</script>
@endif
