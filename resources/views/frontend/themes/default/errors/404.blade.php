{{--
    The default theme's 404.

    Themes::errorView() prefers a theme's own errors/{code}.blade.php over the
    shared resources/views/errors/{code}.blade.php, so this file is what a
    visitor on the default theme actually gets. A theme that ships no such file
    falls back to the shared page instead — nothing here is required for the
    storefront to work.

    Deliberately the same chrome as a real page of this theme (its own header and
    footer, its Tailwind tokens, the same admin/vendor/delivery links) rather
    than the shared page's standalone shell: someone who hits a dead URL is still
    on the site, and should be able to navigate away from it. That is why the
    page carries one "back home" button and no second row of suggested links —
    the header directly above it is already the nav.

    This does mean the page needs the asset pipeline, the settings table and the
    CMS pages, none of which the shared shell needs. That is the trade being
    made, and it is why the shared page remains as the fallback.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    {{-- A 404 must never be indexed, and must never claim a canonical URL for a
         page that does not exist — so partials.seo-meta is deliberately absent
         here (it derives both from $page). --}}
    <meta name="robots" content="noindex, nofollow">
    @include('partials.custom-code-head')
</head>
<body class="bg-white text-zinc-800 antialiased">

@include('frontend.themes.default.partials.header', [
    'navPages' => \App\Support\Frontend::navPages(),
    'showVendorLogin' => \App\Support\Frontend::showVendorLogin(),
    'showDeliveryLogin' => \App\Support\Frontend::showDeliveryLogin(),
])

<main>
    <section class="mx-auto flex max-w-4xl flex-col items-center gap-8 px-6 py-24 text-center sm:flex-row sm:gap-16 sm:text-left">
        <h1 class="text-7xl font-extrabold leading-none tracking-tight text-zinc-900 sm:text-8xl">404</h1>

        <div>
            <h2 class="text-2xl font-bold text-zinc-900 sm:text-3xl">{{ __('Sorry, page not found') }}</h2>
            <p class="mt-3 text-zinc-600">{{ __('The page you requested could not be found. It may have been moved, renamed, or removed.') }}</p>

            <a href="{{ url('/') }}" class="mt-8 inline-block rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                {{ __('Back to homepage') }}
            </a>
        </div>
    </section>
</main>

@include('frontend.themes.default.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>
