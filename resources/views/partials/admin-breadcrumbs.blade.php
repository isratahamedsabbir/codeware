@php
    // $routeName can be passed explicitly by a component that @includes this
    // partial from inside its own reactive template (rather than via the
    // @push('page-header-actions') stack, which only ever renders once, on
    // the initial full-page load) — request()->route() reflects Livewire's
    // internal update endpoint, not the page's own route, on every
    // subsequent wire:click round trip, breaking the breadcrumb otherwise.
    $routeName ??= request()->route()?->getName() ?? 'admin.dashboard';
    $segments  = explode('.', $routeName);
    $resource  = $segments[1] ?? 'dashboard';
    $pageTitle = $title ?? ucwords(str_replace(['-', '_'], ' ', $resource));
    $sectionMap = [
        'product-categories' => ['Products', 'admin.product-categories'],
        'products'           => ['Products', 'admin.products'],
        'orders'             => ['Sales', 'admin.orders'],
        'coupons'            => ['Sales', 'admin.coupons'],
        'shipping-methods'   => ['Sales', 'admin.shipping-methods'],
        'reports'            => ['Sales', 'admin.reports'],
        'post-categories'    => ['Blog', 'admin.post-categories'],
        'tags'               => ['Blog', 'admin.tags'],
        'posts'              => ['Blog', 'admin.posts'],
        'media-library'      => ['Library & System', 'admin.media-library'],
        'settings'           => ['Library & System', 'admin.settings'],
        'seo'                => ['Library & System', 'admin.seo'],
        'social'             => ['Library & System', 'admin.social'],
        'features'           => ['Library & System', 'admin.features'],
        'email-templates'    => ['Library & System', 'admin.email-templates'],
        'file-manager'       => ['Library & System', 'admin.file-manager'],
        'menu'               => ['Library & System', 'admin.menu'],
        'pages'              => ['Pages', 'admin.pages'],
        'cms'                => ['Pages', 'admin.pages'],
        'history'            => ['Library & System', 'admin.history'],
        'payment-gateways'   => ['Library & System', 'admin.payment-gateways'],
        'roles'              => ['Access Control', 'admin.roles'],
        'permissions'        => ['Access Control', 'admin.permissions'],
        'users'              => ['Access Control', 'admin.users'],
        'languages'          => [__('Localization'), 'admin.languages'],
        'translations'       => [__('Localization'), 'admin.translations'],
        'advance'            => ['Advance', 'admin.advance.sitemap'],
        'countries'          => ['Location', 'admin.countries'],
        'divisions'          => ['Location', 'admin.countries'],
        'districts'          => ['Location', 'admin.countries'],
        'upazilas'           => ['Location', 'admin.countries'],
    ];
    $section = $sectionMap[$resource] ?? null;
@endphp

<flux:breadcrumbs>
    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate.hover>Home</flux:breadcrumbs.item>
    @if ($section)
        <flux:breadcrumbs.item :href="route($section[1])" wire:navigate.hover>{{ $section[0] }}</flux:breadcrumbs.item>
    @endif
    <flux:breadcrumbs.item>{{ $pageTitle }}</flux:breadcrumbs.item>
</flux:breadcrumbs>
