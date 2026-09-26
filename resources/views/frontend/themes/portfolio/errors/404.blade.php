{{--
    The portfolio theme's own "page not found" — served instead of the shared
    resources/views/errors/404.blade.php whenever this theme is the active one
    (see Themes::errorView() and the render callback in bootstrap/app.php).

    The portfolio is a one-pager, so the useful suggestions are its own sections
    rather than other pages: the visitor who mistyped a section URL is far more
    likely to want to get back to the work than to go shopping. The accent is
    the theme's own light-mode --pf-primary from public/themes/portfolio/style.css
    rather than read from the ecommerce theme settings the shared shell falls
    back to, so a portfolio 404 still looks like the portfolio.

    Deliberately not built from this theme's header/footer partials — a themed
    404 shouldn't depend on the storefront views that might be missing.
--}}
<x-errors.page
    :code="404"
    :title="__('Nothing here')"
    :message="__('This portfolio is a single page — the page you are looking for may have been moved, renamed, or never existed.')"
    :brand="'#059669'"
    :links="[
        ['label' => __('Selected work'), 'href' => url('/').'#projects'],
        ['label' => __('Experience'), 'href' => url('/').'#experience'],
        ['label' => __('Technology'), 'href' => url('/').'#technology'],
        ['label' => __('Contact'), 'href' => url('/').'#contact'],
    ]"
/>
