{{--
    The default theme's own "page not found" — served instead of the shared
    resources/views/errors/404.blade.php whenever this theme is the active one
    (see Themes::errorView() and the render callback in bootstrap/app.php).

    The default theme is the general-purpose site theme: a landing page plus
    ordinary CMS content pages, which is why its 404 points back at the site's
    own published pages rather than at shop or portfolio sections. Those pages
    come from Frontend::navPages(), which already returns nothing for a theme
    without a page.blade.php, so the list can't advertise a page this theme
    can't render.

    The accent is the theme's own --color-primary from resources/css/app.css
    rather than the ecommerce brand colours the shared shell falls back to, so
    a default 404 looks like the default theme instead of the shop.

    Deliberately not built from this theme's header/footer partials — a themed
    404 shouldn't depend on the storefront views that might be missing. The
    page query is behind a try/catch for the same reason the shell guards its
    Setting reads: a 404 is often rendered because the database is the problem.
--}}
@php
    $pages = [];

    try {
        $pages = \App\Support\Frontend::navPages()
            ->reject(fn ($page) => $page->slug === 'home')
            ->take(5)
            ->map(fn ($page) => [
                'label' => $page->getTranslation('title', 'en', false),
                'href' => route('page', $page->slug),
            ])
            ->values()
            ->all();
    } catch (\Throwable) {
        // No page list is better than an error page that fails to render.
    }
@endphp

<x-errors.page
    :code="404"
    :title="__('Page not found')"
    :message="__('We could not find the page you are looking for. It may have been moved, renamed, or removed.')"
    :brand="'#1e7bc4'"
    :links="$pages"
/>
