{{--
    The ecommerce theme's 404.

    Themes::errorView() prefers a theme's own errors/{code}.blade.php over the
    shared resources/views/errors/{code}.blade.php, so this file is what a
    visitor on the ecommerce theme actually gets. A theme that ships no such file
    falls back to the shared page instead — nothing here is required for the
    storefront to work.

    Wears the storefront's own chrome (header with the category dropdown and
    search, the four-column footer) and its own colour tokens, so a dead product
    or category URL still looks like the shop. Everything is expressed through
    the --color-* custom properties partials.head injects from Theme Settings, so
    the accent here follows the merchant's chosen brand colour.

    The header is handed the frontend menu explicitly because on a real page the
    controller passes it in (see FrontendController) and the partial reads it
    unguarded at the nav's top level.

    Like the default theme's 404 this needs the asset pipeline and the settings
    table, which the shared shell does not — that is the trade, and the reason
    the shared page stays as the fallback.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    {{-- A 404 must never be indexed, and must never claim a canonical URL for a
         page that does not exist — so partials.seo-meta is deliberately absent
         here (it derives both from $page). --}}
    <meta name="robots" content="noindex, nofollow">
    @include('partials.custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('frontend.themes.ecommerce.partials.header', [
    'menuItems' => \App\Support\Frontend::menuItems(),
])

<main>
    <section class="mx-auto flex max-w-4xl flex-col items-center gap-10 px-4 py-20 text-center sm:flex-row sm:gap-16 sm:px-6 sm:text-left">
        <h1 class="text-8xl font-black leading-none tracking-tighter text-sf-heading">404</h1>

        <div>
            <h2 class="text-2xl font-bold text-sf-heading sm:text-3xl">{{ __('Sorry, page not found') }}</h2>
            <p class="mt-3 text-sf-text/70">{{ __('The page you requested could not be found. It may have been moved, renamed, or no longer in stock.') }}</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3 sm:justify-start">
                <a href="{{ route('shop') }}" class="rounded-card bg-sf-button px-6 py-3 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
                    {{ __('Browse the shop') }}
                </a>
                <a href="{{ url('/') }}" class="rounded-card border border-zinc-200 px-6 py-3 text-sm font-semibold text-sf-heading transition hover:bg-zinc-50">
                    {{ __('Back to homepage') }}
                </a>
            </div>
        </div>
    </section>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>
