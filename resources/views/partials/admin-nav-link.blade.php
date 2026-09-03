@php
    $href = $link->route_name && \Illuminate\Support\Facades\Route::has($link->route_name)
        ? route($link->route_name)
        : ($link->url ?? '#');
    $isActive = $link->route_name
        && (request()->routeIs($link->route_name) || request()->routeIs($link->route_name.'.*'));
    $iconName = \App\Models\MenuItem::iconExists($link->icon) ? $link->icon : 'link';
    $navigationStyle = $navigationStyle ?? 'submenu';
@endphp
<a href="{{ $href }}" wire:navigate.hover title="{{ __($link->label) }}"
    class="admin-nav-item admin-nav-item--{{ $navigationStyle }} {{ $isActive ? 'admin-nav-active' : '' }}">
    <span class="admin-nav-item-icon"> 
        <x-dynamic-component :component="'flux::icon.'.$iconName" class="size-4.5" />
    </span>
    <span>{{ __($link->label) }}</span>
</a>
