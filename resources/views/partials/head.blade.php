<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ \App\Support\Seo\SeoResolver::resolve(request(), $page ?? null, $title ?? null)->title }}</title>

@php
    $siteIcon = \App\Models\Setting::get('site_icon');
    $favicon = \App\Models\Setting::get('favicon');
@endphp
@if ($favicon)
    <link rel="icon" href="{{ $favicon }}" type="{{ Str::endsWith($favicon, '.svg') ? 'image/svg+xml' : 'image/x-icon' }}" sizes="any">
@else
    <link rel="icon" href="/favicon/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
@endif
<link rel="apple-touch-icon" href="{{ $siteIcon ?: '/favicon/apple-touch-icon.png' }}">
<link rel="manifest" href="/favicon/site.webmanifest">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
<style>[x-cloak]{display:none!important}</style>

@php
    // A theme's own colours (Theme Settings → its own Colors panel), read from
    // its theme.json and emitted as :root custom properties.
    //
    // Theme-agnostic on purpose. This used to be gated on the ecommerce theme by
    // name, which meant a new theme could add colour settings to its
    // settings.blade.php, see them save, and watch them do nothing — the one
    // thing a theme author cannot debug from their own theme folder. Now any
    // theme declaring these field names gets them applied, and a theme declaring
    // none emits no block at all.
    //
    // Rendered after the compiled CSS so these tokens win the cascade — same
    // pattern as layouts/admin.blade.php.
    $storefrontTheme = \App\Support\Themes::active();
    $storefrontColor = fn (string $field) => theme_color($field, null, $storefrontTheme);

    // The brand token drives links, active states and badges, and is what the
    // other areas fall back to, so it is the one that can be answered by either
    // of two fields. "?:", not "??": a theme's theme.json carries a key for
    // every field its form declares, so an unset accent is present-and-blank
    // rather than absent, and a null coalesce would stop there and drop the
    // primary colour with it.
    $storefrontColors = array_filter([
        '--color-brand' => $storefrontColor('accent_color') ?: $storefrontColor('primary_color'),
        '--color-secondary' => $storefrontColor('secondary_color'),
        '--color-sf-header' => $storefrontColor('header_bg_color'),
        '--color-sf-header-text' => $storefrontColor('header_text_color'),
        '--color-sf-nav' => $storefrontColor('nav_bg_color'),
        '--color-sf-nav-text' => $storefrontColor('nav_text_color'),
        '--color-sf-footer' => $storefrontColor('footer_bg_color'),
        '--color-sf-footer-text' => $storefrontColor('footer_text_color'),
        '--color-sf-footer-bottom' => $storefrontColor('footer_bottom_color'),
        '--color-sf-button' => $storefrontColor('button_bg_color'),
        '--color-sf-button-text' => $storefrontColor('button_text_color'),
        '--color-sf-price' => $storefrontColor('price_color'),
        '--color-sf-heading' => $storefrontColor('heading_color'),
        '--color-sf-text' => $storefrontColor('text_color'),
        '--color-page-bg' => $storefrontColor('page_bg_color'),
        '--color-sale' => $storefrontColor('sale_color'),
    ], fn (?string $value) => $value !== null && $value !== '');
@endphp
@if ($storefrontColors)
    <style>
        :root {
            @foreach ($storefrontColors as $var => $value)
                {{ $var }}: {{ $value }};
            @endforeach
        }
    </style>
@endif
