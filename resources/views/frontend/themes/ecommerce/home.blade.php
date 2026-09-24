<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    @include('partials.custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    // Hero slider images (Theme Settings → Homepage banners); installs that
    // predate the slider fall back to the single hero image.
    $heroSlides = json_decode((string) \App\Models\Setting::get('home_hero_slides', ''), true);
    $heroSlides = is_array($heroSlides)
        ? array_values(array_filter($heroSlides, 'filled'))
        : array_values(array_filter([\App\Models\Setting::get('home_hero_image')], 'filled'));
    $promoImage1 = \App\Models\Setting::get('home_promo_banner_1');
    $promoImage2 = \App\Models\Setting::get('home_promo_banner_2');

    // Promo tile links (Theme Settings): a site path or an http(s) URL —
    // anything else (blank, javascript:, …) falls back to the Shop page.
    $promoLink = function (string $key): string {
        $link = trim((string) \App\Models\Setting::get($key));

        return preg_match('#^(/(?!/)|https?://)#i', $link) ? $link : route('shop');
    };

    $featured = \App\Models\Product::active()
        ->featured()
        ->with(['categories.page', 'brand', 'tags', 'page'])
        ->orderBy('sort_order')
        ->limit(10)
        ->get();

    $homeCategories = \App\Models\ProductCategory::active()
        ->where('featured', true)
        ->with(['page', 'parent'])
        ->withCount(['products' => fn ($q) => $q->active()])
        ->orderBy('sort_order')
        ->get()
        ->filter(fn ($category) => $category->page !== null)
        ->take(10);

    $homeBrands = \App\Models\ProductBrand::active()
        ->orderBy('sort_order')
        ->take(10)
        ->get();

    $newArrivals = \App\Models\Product::active()
        ->with(['categories.page', 'brand', 'tags', 'page'])
        ->latest()
        ->limit(8)
        ->get();

    // Ranked by total units sold on non-cancelled orders.
    $bestSellers = \App\Models\Product::active()
        ->with(['categories.page', 'brand', 'tags', 'page'])
        ->withSum([
            'orderItems as sold_quantity' => fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled')),
        ], 'quantity')
        ->whereHas('orderItems', fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled')))
        ->orderByDesc('sold_quantity')
        ->limit(8)
        ->get();
@endphp

@include('frontend.themes.ecommerce.partials.header')

<main>
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6">
        <section class="mt-6 grid w-full grid-cols-1 gap-4 lg:h-[440px] lg:grid-cols-3">
            <a href="{{ route('shop') }}"
                @if (count($heroSlides) > 1)
                    x-data="{
                        active: 0,
                        count: {{ count($heroSlides) }},
                        timer: null,
                        start() { this.stop(); this.timer = setInterval(() => this.go(this.active + 1), 5000); },
                        stop() { clearInterval(this.timer); },
                        go(i) { this.active = (i + this.count) % this.count; },
                    }"
                    x-init="start()"
                    @mouseenter="stop()" @mouseleave="start()"
                @endif
                class="group/hero relative block h-[300px] overflow-hidden rounded-card lg:col-span-2 lg:h-full">
                @if ($heroSlides !== [])
                    @foreach ($heroSlides as $i => $slide)
                        <img src="{{ $slide }}" alt="{{ $siteName }}" @if ($i > 0) loading="lazy" @endif
                            @if (count($heroSlides) > 1)
                                :class="active === {{ $i }} ? '!opacity-100 scale-100' : 'opacity-0 scale-105'"
                            @endif
                            class="absolute inset-0 h-full w-full object-cover transition duration-1000 ease-out {{ $i === 0 ? '' : 'opacity-0' }}">
                    @endforeach

                    @if (count($heroSlides) > 1)
                        <button type="button" @click.prevent="go(active - 1)" aria-label="{{ __('Previous slide') }}"
                            class="absolute left-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-sf-text opacity-0 shadow-md backdrop-blur transition hover:bg-white group-hover/hero:opacity-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        </button>
                        <button type="button" @click.prevent="go(active + 1)" aria-label="{{ __('Next slide') }}"
                            class="absolute right-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-sf-text opacity-0 shadow-md backdrop-blur transition hover:bg-white group-hover/hero:opacity-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                        <div class="absolute bottom-4 right-6 z-10 flex items-center gap-1.5 md:bottom-8 md:right-8">
                            @foreach ($heroSlides as $i => $slide)
                                <button type="button" @click.prevent="go({{ $i }})" aria-label="{{ __('Slide :n', ['n' => $i + 1]) }}"
                                    :class="active === {{ $i }} ? '!w-6 !bg-white' : 'hover:bg-white/80'"
                                    class="h-2 w-2 rounded-full bg-white/50 transition-all duration-300"></button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex h-full w-full items-center bg-gradient-to-br from-brand to-emerald-800 px-8 md:px-12">
                    </div>
                @endif
                {{-- The hero is image-only; this keeps the page's main heading for
                     search engines and screen readers. --}}
                <h1 class="sr-only">{{ $siteName }}</h1>
            </a>

            <div class="hidden flex-col gap-4 lg:flex">
                @foreach ([
                    ['image' => $promoImage1, 'label' => __('New arrivals'), 'url' => $promoLink('theme_ecommerce_promo_1_link')],
                    ['image' => $promoImage2, 'label' => __('Best deals'), 'url' => $promoLink('theme_ecommerce_promo_2_link')],
                ] as $promo)
                    <a href="{{ $promo['url'] }}" class="group/promo relative block h-1/2 overflow-hidden rounded-card">
                        @if ($promo['image'])
                            <img src="{{ $promo['image'] }}" alt="{{ $promo['label'] }}" class="h-full w-full object-cover transition duration-500 group-hover/promo:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-secondary to-brand">
                            </div>
                        @endif
                        <span class="absolute inset-x-0 bottom-0 p-4 text-lg font-bold uppercase text-white">{{ $promo['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    @if ($homeCategories->isNotEmpty())
        <section class="mt-8 bg-white py-8 lg:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold uppercase tracking-wide text-sf-text md:text-2xl">{{ __('Shop by category') }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Explore our product categories') }}</p>
                    </div>
                    <a href="{{ route('shop') }}" class="shrink-0 text-sm font-semibold text-brand hover:underline">{{ __('View all') }} →</a>
                </div>

                <div class="mt-5 rounded-card bg-gray-100 p-3 md:p-4">
                    <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-2 md:grid-cols-5 md:gap-4 lg:grid-cols-6">
                        @foreach ($homeCategories as $category)
                            <a href="{{ route('shop.category', $category->slug) }}"
                                class="flex h-[104px] flex-col items-center justify-center gap-1.5 rounded-card bg-white p-2.5 text-center shadow-sm transition hover:shadow-md md:h-[131px] md:p-3">
                                @if ($category->icon)
                                    <img src="{{ $category->icon }}" alt="" class="h-[50px] w-[50px] rounded-lg object-contain">
                                @else
                                    <span class="flex h-[50px] w-[50px] items-center justify-center rounded-lg bg-gray-50 text-zinc-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7 12 3l9.75 4L12 11 2.25 7Zm0 0v10L12 21l9.75-4V7M12 11v10" />
                                        </svg>
                                    </span>
                                @endif
                                <span class="w-full truncate text-xs font-semibold text-zinc-700 md:text-sm">{{ $category->name }}</span>
                                @if ($category->products_count > 0)
                                    <span class="text-[11px] text-gray-500">{{ $category->products_count }} {{ __('items') }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
            @include('frontend.themes.ecommerce.partials.section-heading', [
                'title' => __('Featured products'),
                'subtitle' => __('Hand-picked products for you'),
            ])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                @foreach ($featured as $product)
                    @include('frontend.themes.ecommerce.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($bestSellers->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            @include('frontend.themes.ecommerce.partials.section-heading', [
                'title' => __('Best sellers'),
                'subtitle' => __('Most loved by our customers'),
            ])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                @foreach ($bestSellers as $product)
                    @include('frontend.themes.ecommerce.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($newArrivals->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            @include('frontend.themes.ecommerce.partials.section-heading', [
                'title' => __('New arrivals'),
                'subtitle' => __('Just added to the store'),
            ])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                @foreach ($newArrivals as $product)
                    @include('frontend.themes.ecommerce.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($homeBrands->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            @include('frontend.themes.ecommerce.partials.section-heading', [
                'title' => __('Shop by brand'),
            ])
            <div class="grid grid-cols-2 gap-3 md:grid-cols-5 md:gap-4">
                @foreach ($homeBrands as $brand)
                    <a href="{{ route('shop.brand', $brand->slug) }}"
                        class="flex min-h-[64px] items-center justify-center rounded-card bg-white p-4 shadow-sm transition hover:shadow-md {{ $brand->logo ? 'grayscale hover:grayscale-0' : '' }}">
                        @if ($brand->logo)
                            <img src="{{ $brand->logo }}" alt="{{ $brand->name }}" class="max-h-12 w-auto object-contain">
                        @else
                            <span class="text-center text-sm font-bold uppercase tracking-wide text-zinc-700">{{ $brand->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
            @include('frontend.themes.ecommerce.partials.section-heading', ['title' => $section->name])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4">
                @foreach ($section->localizedCards() as $card)
                    <div class="group overflow-hidden rounded-card bg-white shadow-sm transition hover:shadow-md">
                        @if ($card['image'])
                            <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100">
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="p-3">
                            @if ($card['title'])
                                <h3 class="font-semibold text-sf-text">{{ $card['title'] }}</h3>
                            @endif
                            @if ($card['description'])
                                <p class="mt-1.5 text-sm text-zinc-500 line-clamp-2">{{ $card['description'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>