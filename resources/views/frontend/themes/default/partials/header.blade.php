{{--
    Default theme header — the sticky site bar with the CMS page nav and the
    portal/account links.

    Kept as a partial rather than inline in page.blade.php so the theme's 404
    (errors/404.blade.php) wears the same chrome as a real page: a visitor who
    lands on a dead URL should still be able to navigate away from it.

    Reads its own settings. Expects the optional $navPages / $currentSlug /
    $showVendorLogin / $showDeliveryLogin from the including view — every one is
    optional, so a page that has no nav to show (the 404) still renders.
--}}
@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteIcon = \App\Models\Setting::get('site_icon');
@endphp

<header class="sticky top-0 z-20 border-b border-zinc-100 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            @if ($siteIcon)
                <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-8 w-auto">
            @endif
            <span class="text-lg font-bold text-zinc-900">{{ $siteName }}</span>
        </a>

        <nav class="hidden items-center gap-6 md:flex">
            @foreach ($navPages ?? [] as $navPage)
                <a href="{{ $navPage->slug === 'home' ? route('home') : route('page', $navPage->slug) }}"
                    class="text-sm font-medium {{ ($currentSlug ?? null) === $navPage->slug ? 'text-primary' : 'text-zinc-600 hover:text-zinc-900' }}">
                    {{ $navPage->getTranslation('title', 'en', false) }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            @auth
                @can('access-admin')
                    <a href="{{ config('app.admin_url') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                        {{ __('Admin Dashboard') }}
                    </a>
                @endcan
                @can('access-vendor-portal')
                    <a href="{{ route('vendor.dashboard') }}" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                        {{ __('Vendor Dashboard') }}
                    </a>
                @endcan
                @can('access-delivery-portal')
                    <a href="{{ route('delivery.dashboard') }}" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                        {{ __('Delivery Dashboard') }}
                    </a>
                @endcan
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="cursor-pointer rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                        {{ __('Logout') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                    {{ __('Admin Login') }}
                </a>
                @if ($showVendorLogin ?? false)
                    <a href="{{ route('vendor.login') }}" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                        {{ __('Vendor Login') }}
                    </a>
                @endif
                @if ($showDeliveryLogin ?? false)
                    <a href="{{ route('delivery.login') }}" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                        {{ __('Delivery Login') }}
                    </a>
                @endif
            @endauth
        </div>
    </div>
</header>
