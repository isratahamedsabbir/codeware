@php
    $routeName = request()->route()?->getName() ?? 'admin.dashboard';
    $segments  = explode('.', $routeName);
    $resource  = $segments[1] ?? 'dashboard';
    $pageTitle = $title ?? ucwords(str_replace(['-', '_'], ' ', $resource));
    $sectionMap = [
        'product-categories' => ['Products', 'admin.product-categories'],
        'products'           => ['Products', 'admin.products'],
        'orders'             => ['Sales', 'admin.orders'],
        'coupons'            => ['Sales', 'admin.coupons'],
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
        'cms'                => ['Pages', 'admin.pages'],
        'history'            => ['Library & System', 'admin.history'],
        'payment-gateways'   => ['Library & System', 'admin.payment-gateways'],
        'roles'              => ['Access Control', 'admin.roles'],
        'permissions'        => ['Access Control', 'admin.permissions'],
        'users'              => ['Access Control', 'admin.users'],
        'languages'          => [__('Localization'), 'admin.languages'],
        'translations'       => [__('Localization'), 'admin.translations'],
        'advance'            => ['Advance', 'admin.advance.sitemap'],
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
