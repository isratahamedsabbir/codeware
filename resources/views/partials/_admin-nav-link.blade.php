@php
    $href = $link->route_name && \Illuminate\Support\Facades\Route::has($link->route_name)
        ? route($link->route_name)
        : ($link->url ?? '#');
    $isActive = $link->route_name
        ? (request()->routeIs($link->route_name) || request()->routeIs($link->route_name.'.*'))
        : ($link->url && request()->url() === $link->url);
    $iconName = \App\Models\MenuItem::iconExists($link->icon) ? $link->icon : 'link';
    $navigationStyle = $navigationStyle ?? 'submenu';
    $badge = $link->badgeCount();
@endphp
<a href="{{ $href }}" wire:navigate.hover title="{{ __($link->label) }}"
    class="admin-nav-item admin-nav-item--{{ $navigationStyle }} {{ $isActive ? 'admin-nav-active' : '' }}"> 
    <span class="admin-nav-item-icon">  
        <x-dynamic-component :component="'flux::icon.'.$iconName" class="size-4.5" />
    </span>    
    <span>{{ __($link->label) }}</span>
    @if ($badge > 0)
        <span class="ml-auto inline-flex min-w-[1.25rem] shrink-0 items-center justify-center rounded-full px-1.5 text-[10px] font-semibold leading-[1.1rem] text-white"
        style="background:#f43f5e" title="{{ $badge }} {{ __('unread') }}">{{ $badge > 99 ? '99+' : $badge }}</span>
    @endif
</a>
