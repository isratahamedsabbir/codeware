{{--
    Global SEO fallback chain: per-page fields (when $page is in scope) win,
    then the admin/seo "Global SEO" settings, then sensible defaults. See
    App\Concerns\HasSeoFields for the per-page fields and Livewire\Admin\Seo\Index
    for the global settings this reads.
--}}
@php
    $seoPage = $page ?? null;

    $seoDescription = $seoPage?->seo_description ?: \App\Models\Setting::get('seo_meta_description');

    $ogTitle = $seoPage?->og_title ?: (\App\Models\Setting::get('seo_og_title') ?: ($title ?? null));
    $ogDescription = $seoPage?->og_description ?: (\App\Models\Setting::get('seo_og_description') ?: $seoDescription);
    $ogImage = $seoPage?->og_image ?: \App\Models\Setting::get('seo_og_image');

    $twitterTitle = $seoPage?->twitter_title ?: (\App\Models\Setting::get('seo_twitter_title') ?: $ogTitle);
    $twitterDescription = $seoPage?->twitter_description ?: (\App\Models\Setting::get('seo_twitter_description') ?: $ogDescription);
    $twitterImage = $seoPage?->twitter_image ?: (\App\Models\Setting::get('seo_twitter_image') ?: $ogImage);

    $noIndex = (bool) ($seoPage?->no_index ?? false);
    $noFollow = (bool) ($seoPage?->no_follow ?? false);

    $canonicalBase = $seoPage?->canonical_base;
    $canonicalSlug = $seoPage?->canonical_slug;
    $canonicalUrl = $canonicalBase && $canonicalSlug
        ? rtrim($canonicalBase, '/').'/'.ltrim($canonicalSlug, '/')
        : url()->current();
@endphp

@if ($noIndex || $noFollow)
    <meta name="robots" content="{{ $noIndex ? 'noindex' : 'index' }}, {{ $noFollow ? 'nofollow' : 'follow' }}">
@endif

@if (filled($seoDescription))
    <meta name="description" content="{{ $seoDescription }}">
@endif

<link rel="canonical" href="{{ $canonicalUrl }}">

<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="{{ \App\Models\Setting::get('site_name', config('app.name')) }}">
@if (filled($ogTitle))
    <meta property="og:title" content="{{ $ogTitle }}">
@endif
@if (filled($ogDescription))
    <meta property="og:description" content="{{ $ogDescription }}">
@endif
@if (filled($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
@endif

<meta name="twitter:card" content="{{ \App\Models\Setting::get('seo_twitter_card', 'summary_large_image') }}">
@if (filled(\App\Models\Setting::get('seo_twitter_site')))
    <meta name="twitter:site" content="{{ \App\Models\Setting::get('seo_twitter_site') }}">
@endif
@if (filled($twitterTitle))
    <meta name="twitter:title" content="{{ $twitterTitle }}">
@endif
@if (filled($twitterDescription))
    <meta name="twitter:description" content="{{ $twitterDescription }}">
@endif
@if (filled($twitterImage))
    <meta name="twitter:image" content="{{ $twitterImage }}">
@endif
