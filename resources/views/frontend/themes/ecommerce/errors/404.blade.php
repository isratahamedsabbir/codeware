{{--
    The ecommerce theme's own "page not found" — served instead of the shared
    resources/views/errors/404.blade.php whenever this theme is the active one
    (see Themes::errorView() and the render callback in bootstrap/app.php).

    Kept to the self-contained error shell rather than this theme's header and
    footer partials: a themed 404 should never depend on the very storefront
    views that might be missing, and the shell needs no @vite or layout to
    render. What makes it this theme's is the shop-flavoured copy, the brand
    accent, and the suggestions below — each one guarded by Themes::has() so a
    deleted template turns into one fewer suggestion instead of a dead link.
--}}
<x-errors.page
    :code="404"
    :title="__('We couldn\'t find that product')"
    :message="__('The page you are looking for may have been moved, renamed, or is no longer in stock.')"
    :brand="\App\Models\Setting::get('theme_ecommerce_accent_color') ?: \App\Models\Setting::get('theme_ecommerce_primary_color')"
    :links="array_values(array_filter([
        \App\Support\Themes::has('shop') ? ['label' => __('Browse the shop'), 'href' => url('/shop')] : null,
        \App\Support\Themes::has('category') ? ['label' => __('Shop by category'), 'href' => url('/shop')] : null,
        \App\Support\Themes::has('page') ? ['label' => __('Contact us'), 'href' => url('/contact')] : null,
        \App\Support\Features::enabled('orders') ? ['label' => __('My cart'), 'href' => url('/cart')] : null,
    ]))"
/>
