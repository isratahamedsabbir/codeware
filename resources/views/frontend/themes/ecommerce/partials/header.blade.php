@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteIcon = \App\Models\Setting::get('site_icon');
    $currentUrl = url()->current();

    $topCategories = \App\Models\ProductCategory::active()
        ->with('page')
        ->orderBy('sort_order')
        ->get()
        ->filter(fn ($category) => $category->page !== null)
        ->take(12);
@endphp

<header class="sticky top-0 z-40">
    <div class="bg-brand text-white">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <label for="mobile-menu" class="cursor-pointer rounded-md p-2 text-white hover:bg-white/10 md:hidden" aria-label="{{ __('Menu') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </label>

                <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2">
                    @if ($siteIcon)
                        <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-9 w-auto">
                    @endif
                    <span class="truncate text-lg font-bold text-white">{{ $siteName }}</span>
                </a>
            </div>

            <form action="{{ route('shop') }}" method="GET" class="relative hidden flex-1 max-w-xl lg:block">
                <input type="text" name="search" value="{{ request()->query('search') }}"
                    placeholder="{{ __('Search products...') }}"
                    class="w-full rounded-full bg-white py-2.5 pl-5 pr-11 text-sm text-gray-700 placeholder-gray-400 outline-none focus:ring-2 focus:ring-white/60">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-brand" aria-label="{{ __('Search') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                </button>
            </form>

            <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                <livewire:frontend.wishlist-count :key="'wishlist-count'" />

                @auth
                    <details class="group relative">
                        <summary
                            class="flex cursor-pointer list-none items-center gap-2 rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-brand transition hover:bg-white/90 [&::-webkit-details-marker]:hidden"
                            aria-label="{{ __('My account') }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand text-xs font-bold uppercase text-white">
                                {{ auth()->user()->initials() }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-open:rotate-180 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="absolute right-0 top-full z-50 mt-2 w-52 rounded-md border border-zinc-100 bg-white py-1.5 shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                            <p class="border-b border-zinc-100 px-3.5 py-2">
                                <span class="block truncate text-sm font-bold text-zinc-800">{{ auth()->user()->name }}</span>
                                <span class="block truncate text-xs text-gray-500">{{ auth()->user()->email }}</span>
                            </p>
                            <a href="{{ route('account.dashboard') }}" class="block px-3.5 py-2 text-sm font-semibold text-zinc-700 transition-colors hover:bg-gray-50 hover:text-brand">{{ __('My Account') }}</a>
                            <a href="{{ route('account.orders') }}" class="block px-3.5 py-2 text-sm font-semibold text-zinc-700 transition-colors hover:bg-gray-50 hover:text-brand">{{ __('My Orders') }}</a>
                            <a href="{{ route('account.profile') }}" class="block px-3.5 py-2 text-sm font-semibold text-zinc-700 transition-colors hover:bg-gray-50 hover:text-brand">{{ __('Profile') }}</a>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-zinc-100 pt-1">
                                @csrf
                                <button type="submit" class="block w-full px-3.5 py-2 text-left text-sm font-semibold text-red-600 transition-colors hover:bg-red-50">{{ __('Log out') }}</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}"
                        class="hidden items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-semibold text-brand transition hover:bg-white/90 sm:flex">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <span>{{ __('Sign in') }}</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <nav class="hidden border-b border-gray-100 bg-white shadow-sm md:block">
        <ul class="mx-auto flex max-w-7xl items-center px-4 sm:px-6">
            <li>
                <a href="{{ route('home') }}"
                    class="inline-block px-3 py-3.5 text-[15px] font-semibold transition-colors {{ request()->routeIs('home') ? 'text-brand' : 'text-[#222] hover:text-brand' }}">
                    {{ __('Home') }}
                </a>
            </li>
            <li>
                <a href="{{ route('shop') }}"
                    class="inline-block px-3 py-3.5 text-[15px] font-semibold transition-colors {{ request()->routeIs('shop', 'products.show', 'shop.category', 'shop.brand', 'shop.tag', 'favorites') ? 'text-brand' : 'text-[#222] hover:text-brand' }}">
                    {{ __('Shop') }}
                </a>
            </li>

            @if ($topCategories->isNotEmpty())
                <li class="group relative">
                    <button type="button" class="flex cursor-pointer items-center gap-1 px-3 py-3.5 text-[15px] font-semibold text-[#222] transition-colors group-hover:text-brand">
                        {{ __('Categories') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="invisible absolute left-0 top-full z-50 min-w-[230px] translate-y-1 rounded-md border border-gray-100 bg-white py-1.5 opacity-0 shadow-[0_8px_24px_rgba(0,0,0,0.08)] transition-all group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                        @foreach ($topCategories as $category)
                            <a href="{{ route('shop.category', $category->slug) }}"
                                class="flex items-center justify-between rounded-sm px-3 py-2 text-sm text-zinc-700 transition-colors hover:bg-gray-50 hover:text-brand">
                                <span class="truncate">{{ $category->name }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                        <a href="{{ route('shop') }}" class="mt-1 block border-t border-gray-100 px-3 py-2 text-sm font-semibold text-brand hover:text-brand-ink">
                            {{ __('View all products') }}
                        </a>
                    </div>
                </li>
            @endif

            @foreach ($menuItems ?? [] as $menuItem)
                <li>
                    <a href="{{ url($menuItem->url) }}"
                        class="inline-block px-3 py-3.5 text-[15px] font-semibold transition-colors {{ url($menuItem->url) === $currentUrl ? 'text-brand' : 'text-[#222] hover:text-brand' }}">
                        {{ $menuItem->label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <input type="checkbox" id="mobile-menu" class="peer hidden">
    <div class="hidden border-b border-gray-100 bg-white px-4 py-4 peer-checked:block md:hidden">
        <form action="{{ route('shop') }}" method="GET" class="relative mb-4">
            <input type="text" name="search" value="{{ request()->query('search') }}"
                placeholder="{{ __('Search products...') }}"
                class="w-full rounded-full border border-zinc-200 bg-gray-50 py-2.5 pl-5 pr-11 text-sm outline-none focus:border-brand">
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-brand" aria-label="{{ __('Search') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                </svg>
            </button>
        </form>

        <nav class="flex flex-col">
            <a href="{{ route('home') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-zinc-800 transition-colors hover:bg-gray-50 hover:text-brand">{{ __('Home') }}</a>
            <a href="{{ route('shop') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-zinc-800 transition-colors hover:bg-gray-50 hover:text-brand">{{ __('Shop') }}</a>
            @if ($topCategories->isNotEmpty())
                <p class="px-3 pb-1 pt-3 text-xs font-bold uppercase tracking-wide text-zinc-400">{{ __('Categories') }}</p>
                @foreach ($topCategories as $category)
                    <a href="{{ route('shop.category', $category->slug) }}" class="rounded-md px-3 py-2 text-sm text-zinc-600 transition-colors hover:bg-gray-50 hover:text-brand">{{ $category->name }}</a>
                @endforeach
            @endif
            @foreach ($menuItems ?? [] as $menuItem)
                <a href="{{ url($menuItem->url) }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-zinc-800 transition-colors hover:bg-gray-50 hover:text-brand">{{ $menuItem->label }}</a>
            @endforeach
            <a href="{{ route('favorites') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-zinc-800 transition-colors hover:bg-gray-50 hover:text-brand">{{ __('My favorites') }}</a>
            @auth
                <p class="px-3 pb-1 pt-3 text-xs font-bold uppercase tracking-wide text-zinc-400">{{ __('My account') }}</p>
                <a href="{{ route('account.dashboard') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-zinc-800 transition-colors hover:bg-gray-50 hover:text-brand">{{ __('My Account') }}</a>
                <a href="{{ route('account.orders') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-zinc-800 transition-colors hover:bg-gray-50 hover:text-brand">{{ __('My Orders') }}</a>
                <a href="{{ route('account.profile') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-zinc-800 transition-colors hover:bg-gray-50 hover:text-brand">{{ __('Profile') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full rounded-full bg-red-50 px-5 py-2.5 text-center text-sm font-semibold text-red-600 transition hover:bg-red-100">{{ __('Log out') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="mt-2 rounded-full bg-brand px-5 py-2.5 text-center text-sm font-semibold text-white transition hover:opacity-90">{{ __('Sign in') }}</a>
            @endauth
        </nav>
    </div>
</header>