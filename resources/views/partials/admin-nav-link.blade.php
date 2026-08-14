@php
    $href = $link->route_name && \Illuminate\Support\Facades\Route::has($link->route_name)
        ? route($link->route_name)
        : ($link->url ?? '#');
    $isActive = $link->route_name
        && (request()->routeIs($link->route_name) || request()->routeIs($link->route_name.'.*'));
    $iconName = \App\Models\MenuItem::iconExists($link->icon) ? $link->icon : 'link';
@endphp
<a href="{{ $href }}" wire:navigate.hover class="admin-nav-item {{ $isActive ? 'admin-nav-active' : '' }}">
    <x-dynamic-component :component="'flux::icon.'.$iconName" class="size-4.5 shrink-0" />
    <span>{{ __($link->label) }}</span>
</a>
