@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteIcon = \App\Models\Setting::get('site_icon');
    $currentUrl = url()->current();

    $headerCategories = \App\Models\ProductCategory::active()
        ->with('page')
        ->orderBy('sort_order')
        ->get()
        ->filter(fn ($category) => $category->page !== null);

    // The dropdown shows top-level categories, each expandable into its
    // children. A child whose parent isn't listed (inactive / no page) is
    // promoted to the top level so it never disappears.
    $headerCategoryIds = $headerCategories->pluck('id')->flip();
    $headerChildrenByParent = $headerCategories
        ->filter(fn ($category) => $category->parent_id && $headerCategoryIds->has($category->parent_id))
        ->groupBy('parent_id');
    $headerTopCategories = $headerCategories
        ->reject(fn ($category) => $category->parent_id && $headerCategoryIds->has($category->parent_id));

    $headerBrands = \App\Models\ProductBrand::active()
        ->orderBy('sort_order')
        ->get();

    $languages = \App\Support\Locale::active();
    $currentLocale = \App\Support\Locale::current();
    $languageSwitcherEnabled = (bool) \App\Models\Setting::get('language_switcher_enabled', true);
@endphp

<header class="sticky top-0 z-40">
    <div class="bg-sf-header text-sf-header-text">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <label for="mobile-menu" class="cursor-pointer rounded-md p-2 text-sf-header-text hover:bg-white/10 md:hidden" aria-label="{{ __('Menu') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </label>

                <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2">
                    @if ($siteIcon)
                        <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-9 w-auto">
                    @endif
                    <span class="truncate text-lg font-bold text-sf-header-text">{{ $siteName }}</span>
                </a>
            </div>

            <div class="relative hidden flex-1 max-w-xl lg:block">
                <livewire:frontend.header-search :key="'header-search'" />
            </div>

            <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                @if ($contactPhone = \App\Models\Setting::get('contact_phone'))
                    <a href="tel:{{ $contactPhone }}"
                        class="hidden items-center gap-1.5 text-base font-semibold text-sf-header-text/80 transition-colors hover:text-sf-header-text lg:flex"
                        aria-label="{{ __('Call us') }}" title="{{ $contactPhone }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        <span class="whitespace-nowrap">{{ $contactPhone }}</span>
                    </a>
                @endif
                @if ($languageSwitcherEnabled && $languages->count() > 1)
                    <details class="group relative">
                        <summary
                            class="flex cursor-pointer list-none items-center gap-1.5 rounded-full bg-white/10 p-2 text-sm font-semibold text-sf-header-text transition hover:bg-white/20 sm:px-3 [&::-webkit-details-marker]:hidden"
                            aria-label="{{ __('Change language') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                            </svg>
                            <span class="hidden text-xs font-bold uppercase sm:inline">{{ $currentLocale }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4 transition-transform group-open:rotate-180 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="absolute right-0 top-full z-50 mt-2 w-44 rounded-card border border-zinc-100 bg-white py-1.5 shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                            @foreach ($languages as $language)
                                <a href="{{ request()->fullUrlWithQuery(['lang' => $language->code]) }}"
                                    class="flex items-center gap-2 px-3.5 py-2 text-sm font-semibold transition-colors {{ $language->code === $currentLocale ? 'text-brand' : 'text-zinc-700 hover:bg-gray-50 hover:text-brand' }}">
                                    @if ($language->flag)
                                        <span>{{ $language->flag }}</span>
                                    @endif
                                    <span class="flex-1 truncate">{{ $language->native_name ?: $language->name }}</span>
                                    @if ($language->code === $currentLocale)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif
                <livewire:frontend.cart-count :key="'cart-count'" />
                <livewire:frontend.wishlist-count :key="'wishlist-count'" />

                @auth
                    <details class="group relative">
                        {{-- Signed in: just the avatar circle (photo, or initials). --}}
                        <summary
                            class="flex cursor-pointer list-none rounded-full ring-2 ring-white/70 transition hover:ring-white group-open:ring-white [&::-webkit-details-marker]:hidden"
                            aria-label="{{ __('My account') }}" title="{{ auth()->user()->name }}">
                            @if (auth()->user()->photo_url)
                                <img src="{{ auth()->user()->photo_url }}" alt="{{ auth()->user()->name }}" class="h-9 w-9 rounded-full object-cover">
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-xs font-bold uppercase text-brand">
                                    {{ auth()->user()->initials() }}
                                </span>
                            @endif
                        </summary>
                        <div class="absolute right-0 top-full z-50 mt-2 w-52 rounded-card border border-zinc-100 bg-white py-1.5 shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                            <p class="border-b border-zinc-100 px-3.5 py-2">
                                <span class="block truncate text-sm font-bold text-sf-text">{{ auth()->user()->name }}</span>
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

    @if ($menuItems->isNotEmpty() || $headerCategories->isNotEmpty() || $headerBrands->isNotEmpty())
        <nav class="hidden border-b border-gray-100 bg-sf-nav shadow-sm md:block">
            <ul class="mx-auto flex max-w-7xl items-center px-4 sm:px-6">
                @if ($headerCategories->isNotEmpty())
                    <li>
                        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                            <button type="button" @click="open = !open" :aria-expanded="open"
                                :class="{ 'text-brand': open }"
                                class="flex cursor-pointer items-center gap-1 px-3 py-3.5 text-[15px] font-semibold text-sf-nav-text transition-colors hover:text-brand">
                                <span>{{ __('Categories') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition @click="open = false"
                                class="absolute left-0 top-full z-50 ml-0 flex w-72 max-h-[min(24rem,60vh)] flex-col rounded-r-lg rounded-b-lg border border-gray-100 bg-white py-1.5 shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                                <div class="min-h-0 overflow-y-auto overscroll-contain">
                                    @foreach ($headerTopCategories as $category)
                                        @include('frontend.themes.ecommerce.partials.header-category-item', [
                                            'category' => $category,
                                            'childrenByParent' => $headerChildrenByParent,
                                            'depth' => 0,
                                        ])
                                    @endforeach
                                </div>
                                <a href="{{ route('shop') }}"
                                    class="block border-t border-gray-100 bg-gray-50 px-4 py-2.5 text-center text-sm font-semibold text-brand transition-colors hover:text-brand/80">
                                    {{ __('All categories') }}
                                </a>
                            </div>
                        </div>
                    </li>
                @endif
                @if ($headerBrands->isNotEmpty())
                    <li>
                        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                            <button type="button" @click="open = !open" :aria-expanded="open"
                                :class="{ 'text-brand': open }"
                                class="flex cursor-pointer items-center gap-1 px-3 py-3.5 text-[15px] font-semibold text-sf-nav-text transition-colors hover:text-brand">
                                <span>{{ __('Brands') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition @click="open = false"
                                class="absolute left-0 top-full z-50 ml-0 flex w-72 max-h-[min(24rem,60vh)] flex-col rounded-r-lg rounded-b-lg border border-gray-100 bg-white py-1.5 shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                                <div class="min-h-0 overflow-y-auto overscroll-contain">
                                    @foreach ($headerBrands as $brand)
                                        <a href="{{ route('shop.brand', $brand->slug) }}"
                                            class="block truncate px-4 py-2 text-sm font-semibold text-zinc-700 transition-colors hover:bg-gray-50 hover:text-brand">
                                            {{ $brand->name }}
                                        </a>
                                    @endforeach
                                </div>
                                <a href="{{ route('shop') }}"
                                    class="block border-t border-gray-100 bg-gray-50 px-4 py-2.5 text-center text-sm font-semibold text-brand transition-colors hover:text-brand/80">
                                    {{ __('All brands') }}
                                </a>
                            </div>
                        </div>
                    </li>
                @endif
                @foreach ($menuItems as $menuItem)
                    <li>
                        <a href="{{ url($menuItem->url) }}"
                            class="inline-block px-3 py-3.5 text-[15px] font-semibold transition-colors {{ url($menuItem->url) === $currentUrl ? 'text-brand' : 'text-sf-nav-text hover:text-brand' }}">
                            {{ $menuItem->label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    <input type="checkbox" id="mobile-menu" class="peer hidden">
    <div class="hidden border-b border-gray-100 bg-white px-4 py-4 peer-checked:block md:hidden">
        <div class="relative mb-4">
                <livewire:frontend.header-search :on-dark="false" :key="'header-search-mobile'" />
            </div>

        <nav class="flex flex-col">
            {{-- Categories and Brands collapse to a single row each so the menu
                 stays short; tap the heading to open the list. --}}
            @php
                $mobileSection = 'flex w-full items-center justify-between rounded-md! px-3 py-2.5 text-sm font-semibold text-sf-text transition-colors hover:bg-gray-50 hover:text-brand';
            @endphp
            @if ($headerCategories->isNotEmpty())
                <div x-data="{ open: false }" class="border-b border-zinc-100">
                    <button type="button" @click="open = ! open" :aria-expanded="open" class="{{ $mobileSection }}">
                        <span>{{ __('Categories') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-400 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
                    </button>
                    <div x-show="open" x-collapse x-cloak class="pb-2">
                        @foreach ($headerTopCategories as $category)
                            @include('frontend.themes.ecommerce.partials.header-category-item', [
                                'category' => $category,
                                'childrenByParent' => $headerChildrenByParent,
                                'depth' => 0,
                            ])
                        @endforeach
                        <a href="{{ route('shop') }}" class="mt-1 block px-3 py-2 text-sm font-semibold text-brand">{{ __('All categories') }} →</a>
                    </div>
                </div>
            @endif
            @if ($headerBrands->isNotEmpty())
                <div x-data="{ open: false }" class="border-b border-zinc-100">
                    <button type="button" @click="open = ! open" :aria-expanded="open" class="{{ $mobileSection }}">
                        <span>{{ __('Brands') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-400 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
                    </button>
                    <div x-show="open" x-collapse x-cloak class="pb-2">
                        @foreach ($headerBrands as $brand)
                            <a href="{{ route('shop.brand', $brand->slug) }}" class="block rounded-md py-2 pl-9 pr-3 text-sm font-semibold text-zinc-700 transition-colors hover:bg-gray-50 hover:text-brand">{{ $brand->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
            @foreach ($menuItems ?? [] as $menuItem)
                <a href="{{ url($menuItem->url) }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-sf-text transition-colors hover:bg-gray-50 hover:text-brand">{{ $menuItem->label }}</a>
            @endforeach
            <a href="{{ route('favorites') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-sf-text transition-colors hover:bg-gray-50 hover:text-brand">{{ __('My favorites') }}</a>
            <a href="{{ route('cart') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-sf-text transition-colors hover:bg-gray-50 hover:text-brand">{{ __('My cart') }}</a>
            @auth
                <p class="px-3 pb-1 pt-3 text-xs font-bold uppercase tracking-wide text-zinc-400">{{ __('My account') }}</p>
                <a href="{{ route('account.dashboard') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-sf-text transition-colors hover:bg-gray-50 hover:text-brand">{{ __('My Account') }}</a>
                <a href="{{ route('account.orders') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-sf-text transition-colors hover:bg-gray-50 hover:text-brand">{{ __('My Orders') }}</a>
                <a href="{{ route('account.profile') }}" class="rounded-md px-3 py-2.5 text-sm font-semibold text-sf-text transition-colors hover:bg-gray-50 hover:text-brand">{{ __('Profile') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full rounded-full bg-red-50 px-5 py-2.5 text-center text-sm font-semibold text-red-600 transition hover:bg-red-100">{{ __('Log out') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="mt-2 rounded-full bg-sf-button px-5 py-2.5 text-center text-sm font-semibold text-sf-button-text transition hover:opacity-90">{{ __('Sign in') }}</a>
            @endauth
        </nav>
    </div>
</header>