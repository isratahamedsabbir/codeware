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
    // The ecommerce theme's primary/secondary colors (editable from its own
    // settings panel on the Theme Settings screen). Applied only when that
    // theme is active, and after the compiled CSS so these :root tokens win
    // the cascade — same pattern as layouts/admin.blade.php.
    $storefrontTheme = \App\Support\Themes::active();
@endphp
@if ($storefrontTheme === 'ecommerce')
    @php
        // Theme Settings → Colors: one setting per storefront area. Blank (or
        // anything that isn't a hex color) keeps the default from app.css.
        $hex = fn (?string $v) => is_string($v) && preg_match('/^#[0-9a-fA-F]{3,8}$/', trim($v)) ? trim($v) : null;
        $storefrontColors = [
            // The accent drives links, active states and badges — and is what
            // the other areas fall back to. Older installs only have "primary".
            '--color-brand' => $hex(\App\Models\Setting::get('theme_ecommerce_accent_color'))
                ?? $hex(\App\Models\Setting::get('theme_ecommerce_primary_color')) ?? '#045b30',
            '--color-secondary' => $hex(\App\Models\Setting::get('theme_ecommerce_secondary_color')),
            '--color-sf-header' => $hex(\App\Models\Setting::get('theme_ecommerce_header_bg_color')),
            '--color-sf-header-text' => $hex(\App\Models\Setting::get('theme_ecommerce_header_text_color')),
            '--color-sf-nav' => $hex(\App\Models\Setting::get('theme_ecommerce_nav_bg_color')),
            '--color-sf-nav-text' => $hex(\App\Models\Setting::get('theme_ecommerce_nav_text_color')),
            '--color-sf-footer' => $hex(\App\Models\Setting::get('theme_ecommerce_footer_bg_color')),
            '--color-sf-footer-text' => $hex(\App\Models\Setting::get('theme_ecommerce_footer_text_color')),
            '--color-sf-footer-bottom' => $hex(\App\Models\Setting::get('theme_ecommerce_footer_bottom_color')),
            '--color-sf-button' => $hex(\App\Models\Setting::get('theme_ecommerce_button_bg_color')),
            '--color-sf-button-text' => $hex(\App\Models\Setting::get('theme_ecommerce_button_text_color')),
            '--color-sf-price' => $hex(\App\Models\Setting::get('theme_ecommerce_price_color')),
            '--color-sf-heading' => $hex(\App\Models\Setting::get('theme_ecommerce_heading_color')),
            '--color-sf-text' => $hex(\App\Models\Setting::get('theme_ecommerce_text_color')),
            '--color-page-bg' => $hex(\App\Models\Setting::get('theme_ecommerce_page_bg_color')),
            '--color-sale' => $hex(\App\Models\Setting::get('theme_ecommerce_sale_color')),
        ];
    @endphp
    <style>
        :root {
            @foreach (array_filter($storefrontColors) as $var => $color)
                {{ $var }}: {{ $color }};
            @endforeach
        }
    </style>
@endif
