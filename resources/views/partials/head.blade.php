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

@php
    // The active theme's chosen typeface (Admin → Theme Settings → Typography),
    // read from its theme.json. See App\Support\ThemeFont.
    $storefrontTheme = \App\Support\Themes::active();
    $storefrontFont = \App\Support\ThemeFont::normalize(
        \App\Support\ThemeSettings::text($storefrontTheme, \App\Support\ThemeSettings::keyFor('font', $storefrontTheme))
    );
    $storefrontFontStack = \App\Support\ThemeFont::stackFor($storefrontFont);
    $storefrontFontPreload = \App\Support\ThemeFont::preloadFor($storefrontFont);

    // Plus Jakarta Sans, self-hosted — see resources/css/fonts.css.
    //
    // Preloaded, and only for the themes that actually render it. A preload is
    // a promise the browser keeps whether or not the file is used, so sending
    // ~27 KB to a page that never draws a glyph of it is a real cost on a
    // phone. Two themes opt out for two different reasons: ecommerce's
    // storefront font stack is a system font (Trebuchet MS), and portfolio
    // has its own self-hosted Instrument Sans, preloaded separately by that
    // theme's own layout. Known opt-out themes are listed here; anything
    // else, including a theme installed later as a zip, gets the preload —
    // because being wrong in that direction costs one duplicate request, while
    // being wrong the other way costs a font that arrives after the text.
    //
    // Superseded the moment a theme picks a font of its own: this preload is for
    // the font a theme renders by default, so a theme that has chosen one gets
    // that file preloaded instead — two preloads would mean two requests for two
    // files where only one is ever drawn.
    $otherFontThemes = ['ecommerce', 'portfolio'];
    $preloadStorefrontFont = $storefrontFontStack === null
        && ! in_array(theme_slug(), $otherFontThemes, true);
@endphp
@if ($storefrontFontPreload)
    {{-- Whichever face the active theme selected. The latin subset only: the
         latin-ext file is reached through its unicode-range, so a page without
         accented characters never asks for it. See ThemeFont::preloadFor(). --}}
    <link rel="preload" href="{{ $storefrontFontPreload }}" as="font" type="font/woff2" crossorigin>
@elseif ($preloadStorefrontFont)
    {{-- The latin subset only. The latin-ext face is reached through its
         unicode-range, so a page without accented characters never asks for it. --}}
    <link rel="preload" href="/fonts/plus-jakarta-sans-latin.woff2" as="font" type="font/woff2" crossorigin>
@endif

@php
    // Which bundle to load. Defaults to the storefront one, which is what every
    // theme template and the vendor/delivery panels want. layouts/app/sidebar
    // passes 'admin': it is the admin panel's own chrome, it renders admin
    // Livewire screens, and it has no business pulling a shopper's stylesheet
    // into the console.
    //
    // 'storefront' resolves per theme: a theme with its own stylesheet is served
    // that, so its page pays only for the utility classes its own templates use
    // rather than for every theme's. A theme that ships none gets the catch-all
    // storefront bundle, which scans them all. See Themes::storefrontEntry().
    $assetBundle = $assetBundle ?? 'storefront';
@endphp
@if ($assetBundle === 'admin')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    @vite([\App\Support\Themes::storefrontEntry()])
@endif
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

@if ($storefrontFontStack)
    {{-- The typeface the active theme selected, if it selected one. A theme
         that has not chosen emits nothing at all, which is the point: its own
         stylesheet keeps the font it was designed around.

         Two deliberate choices here.

         The @font-face declarations are emitted for whichever webfont was
         chosen rather than relying on the one already in the theme's CSS or in
         resources/css/fonts.css. Which stylesheet a page loads depends on the
         theme, so a face declared only in the storefront bundle does not exist
         on a theme that ships its own — picking Plus Jakarta on portfolio would
         otherwise download nothing and render the fallback. See
         ThemeFont::facesFor().

         And the override is !important. The three themes declare their font
         three different ways, none of which is a token this partial sets:
         default reads var(--font-sans) on body, ecommerce has a @utility class
         on body, portfolio has a theme class on body. The two class-based ones
         outrank a plain body rule, so matching each selector would mean naming
         every theme's internals here — and a theme installed later would need a
         new line in this file to be overridable at all. Marking the declaration
         important is what makes the setting work uniformly.

         It is still the right blast radius. It applies to the body element only,
         so it changes what the page inherits; any element that names its own
         font — portfolio's .pf-mono, a display heading — keeps it, because a
         descendant's own declaration outranks an inherited value however the
         ancestor was set. A webfont that fails to load falls through to the
         system tail in its own stack.

         Printed unescaped: a font stack and a @font-face are quoted with single
         quotes, which double-brace escaping would turn into HTML entities, and
         entities are not decoded inside a <style> element — the declaration
         would reach the parser invalid and be dropped. Safe because every value
         here comes from a hard-coded table in ThemeFont and none of them
         interpolates the stored setting. --}}
    <style>
        @foreach (\App\Support\ThemeFont::facesFor($storefrontFont) as $faces)
            @foreach ($faces as $face)
                @font-face {
                    font-family: {!! \App\Support\ThemeFont::familiesFor($storefrontFont)[$storefrontFont] !!};
                    font-style: normal;
                    font-weight: {!! \App\Support\ThemeFont::weightsFor($storefrontFont)[$storefrontFont] !!};
                    font-display: swap;
                    src: url('{!! $face['url'] !!}') format('woff2');
                    unicode-range: {!! $face['unicodeRange'] !!};
                }
            @endforeach
        @endforeach
        body { font-family: {!! $storefrontFontStack !!} !important; }
    </style>
@endif
