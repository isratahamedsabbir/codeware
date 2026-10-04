<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    // Hero slider (Theme Settings → Banners): each slide {image, title,
    // description, url}; older image-only data still renders.
    $heroSlides = \App\Support\HeroSlides::forStorefront();
    $promoImage1 = \App\Models\Setting::get('home_promo_banner_1');
    $promoImage2 = \App\Models\Setting::get('home_promo_banner_2');

    // Promo tile links (Theme Settings): a site path or an http(s) URL ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â
    // anything else (blank, javascript:, …) falls back to the Shop page. Read
    // from the ecommerce theme's own theme.json.
    $promoLink = fn (string $key): string => \App\Support\HeroSlides::safeUrl(\App\Support\ThemeSettings::text('ecommerce', $key));

    $featured = \App\Models\Product::active()
        ->featured()
        ->with(['categories.page', 'brand', 'tags', 'page'])
        ->withSoldQuantity()
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
        ->take(20);

    $homeBrands = \App\Models\ProductBrand::active()
        ->orderBy('sort_order')
        ->take(10)
        ->get();

    $newArrivals = \App\Models\Product::active()
        ->with(['categories.page', 'brand', 'tags', 'page'])
        ->withSoldQuantity()
        ->latest()
        ->limit(8)
        ->get();

    // Ranked by total units sold on non-cancelled orders.
    $bestSellers = \App\Models\Product::active()
        ->with(['categories.page', 'brand', 'tags', 'page'])
        ->withSoldQuantity()
        ->whereHas('orderItems', fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled')))
        ->orderByDesc('sold_quantity')
        ->limit(8)
        ->get();
@endphp

@include('theme-ecommerce::partials._header')

<main>
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6">
        {{-- The 3-up hero (main banner + two stacked promos) starts at xl, not lg.
             At lg (1024px) the main banner was still two columns of a 3-column grid
             — about 590px wide and 440px tall — so a 1200x440 crop was being pushed
             through a portrait-shaped box and lost most of its middle. Between lg
             and xl the promos sit side by side under a full-width banner instead,
             which is what the stacking order already did below lg. --}}
        <section class="mt-6 grid w-full grid-cols-1 gap-4 xl:h-[440px] xl:grid-cols-3">
            <a href="{{ $heroSlides[0]['url'] ?? route('shop') }}"
                @if (count($heroSlides) > 1)
                    :href="links[active]"
                    x-data="{
                        active: 0,
                        count: {{ count($heroSlides) }},
                        links: @js(array_column($heroSlides, 'url')),
                        timer: null,
                        start() { this.stop(); this.timer = setInterval(() => this.go(this.active + 1), 5000); },
                        stop() { clearInterval(this.timer); },
                        go(i) { this.active = (i + this.count) % this.count; },
                    }"
                    x-init="start()"
                    @mouseenter="stop()" @mouseleave="start()"
                @endif
                class="group/hero relative block h-[300px] overflow-hidden rounded-card sm:h-[360px] xl:col-span-2 xl:h-full">
                @if ($heroSlides !== [])
                    @foreach ($heroSlides as $i => $slide)
                        @php $hasText = filled($slide['title']) || filled($slide['description']); @endphp
                        <div
                            @if (count($heroSlides) > 1)
                                :class="active === {{ $i }} ? '!opacity-100 z-[1]' : 'opacity-0'"
                                :aria-hidden="active !== {{ $i }}"
                            @endif
                            class="absolute inset-0 transition-opacity duration-1000 ease-out {{ $i === 0 ? '' : 'opacity-0' }}">
                            <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] ?: $siteName }}" @if ($i > 0) loading="lazy" @else fetchpriority="high" @endif
                                width="1200" height="440"
                                @if (count($heroSlides) > 1)
                                    :class="active === {{ $i }} ? 'scale-100' : 'scale-105'"
                                @endif
                                class="h-full w-full object-cover transition-transform duration-[1600ms] ease-out">

                            @if ($hasText)
                                {{-- Text only gets a shade behind it when there's text to read. --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/15 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-6 pb-12 md:p-10 md:pb-14">
                                    @if (filled($slide['title']))
                                        <h2 class="max-w-xl text-2xl font-bold leading-tight text-white drop-shadow md:text-4xl">{{ $slide['title'] }}</h2>
                                    @endif
                                    @if (filled($slide['description']))
                                        <p class="mt-2 max-w-lg text-sm text-white/90 drop-shadow md:text-base">{{ $slide['description'] }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
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
                    {{-- No slides configured: show the site name as visible text so
                         the first screen has a real Largest Contentful Paint
                         element (a bare gradient gives Lighthouse NO_LCP, and
                         PageSpeed then reports no Performance score at all). --}}
                    <div class="flex h-full w-full items-center bg-gradient-to-br from-brand to-emerald-800 px-8 md:px-12">
                        <h1 class="max-w-xl text-3xl font-bold leading-tight text-white md:text-5xl">{{ $siteName }}</h1>
                    </div>
                @endif
                {{-- The hero is image-only; this keeps the page's main heading for
                     search engines and screen readers. --}}
                @if ($heroSlides !== [])
                    <h1 class="sr-only">{{ $siteName }}</h1>
                @endif
            </a>

            <div class="grid grid-cols-2 gap-4 xl:flex xl:flex-col">
                @foreach ([
                    ['image' => $promoImage1, 'label' => __('New arrivals'), 'url' => $promoLink('theme_ecommerce_promo_1_link')],
                    ['image' => $promoImage2, 'label' => __('Best deals'), 'url' => $promoLink('theme_ecommerce_promo_2_link')],
                ] as $promo)
                    <a href="{{ $promo['url'] }}" class="group/promo relative block h-32 overflow-hidden rounded-card sm:h-44 lg:h-56 xl:h-1/2">
                        @if ($promo['image'])
                            <img src="{{ $promo['image'] }}" alt="{{ $promo['label'] }}" loading="lazy" decoding="async" width="600" height="400" class="h-full w-full object-cover transition duration-500 group-hover/promo:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-secondary to-brand">
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    @if ($homeCategories->isNotEmpty())
        <section class="mt-8 bg-white py-8 lg:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">

                {{-- A horizontal scroller of the same category card used before,
                     with arrows to page it. Named "Shop by category" rather than
                     "Featured categories" so the heading matches what the row
                     is: a department index, with the featured ones shown here.
                     It is a real <h2>, which also gives the section an
                     accessible name. --}}
                @include('theme-ecommerce::partials._section-heading', [
                    'title' => __('Shop by category'),
                    'subtitle' => __('Browse our most popular departments'),
                ])

                <div class="relative mt-2"
                    x-data="{ scrollBy(dir) { this.$refs.track.scrollBy({ left: dir * this.$refs.track.clientWidth * 0.8, behavior: 'smooth' }); } }">
                    <button type="button" @click="scrollBy(-1)" aria-label="{{ __('Previous') }}"
                        class="absolute -left-3 top-1/2 z-10 flex h-9 w-9 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-500 shadow-md transition hover:text-brand sm:-left-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    </button>

                    <div x-ref="track" class="no-scrollbar flex snap-x snap-mandatory scroll-smooth gap-2.5 overflow-x-auto px-1 py-1 sm:gap-3 md:gap-4">
                        @foreach ($homeCategories as $category)
                            <a href="{{ route('shop.category', $category->slug) }}"
                                class="group flex h-26.5 w-32.5 shrink-0 snap-start flex-col items-center justify-center gap-1.5 rounded-card border border-zinc-200/80 bg-white p-2 text-center shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand/40 hover:shadow-md sm:h-31 sm:w-37.5 sm:p-2.5 md:h-34 md:w-40 lg:h-38 lg:w-43">
                                @if ($category->icon)
                                    <img src="{{ $category->icon }}" alt="" loading="lazy" decoding="async" width="56" height="56"
                                        class="h-10 w-10 rounded-lg bg-zinc-50 object-contain p-0.5 transition duration-200 group-hover:scale-105 sm:h-12 sm:w-12 lg:h-14 lg:w-14">
                                @else
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-50 text-zinc-500 transition duration-200 group-hover:text-brand sm:h-12 sm:w-12 lg:h-14 lg:w-14">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6 lg:h-7 lg:w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7 12 3l9.75 4L12 11 2.25 7Zm0 0v10L12 21l9.75-4V7M12 11v10" />
                                        </svg>
                                    </span>
                                @endif
                                {{-- Clamped to two lines instead of truncated: the old
                                     single-line ellipsis cut off names like
                                     "Home & Kitchen Appliances", which is the name the
                                     shopper is scanning for. --}}
                                <span class="line-clamp-2 w-full text-[11px] font-semibold leading-tight text-zinc-700 sm:text-sm">{{ $category->name }}</span>
                                @if ($category->products_count > 0)
                                    <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-500">{{ $category->products_count }} {{ __('items') }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <button type="button" @click="scrollBy(1)" aria-label="{{ __('Next') }}"
                        class="absolute -right-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 translate-x-1/2 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-500 shadow-md transition hover:text-brand sm:-right-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </button>
                </div>
            </div>
        </section>
    @endif

    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
            @include('theme-ecommerce::partials._section-heading', [
                'title' => __('Featured products'),
                'subtitle' => __('Hand-picked products for you'),
            ])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                @foreach ($featured as $product)
                    @include('theme-ecommerce::partials._product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($bestSellers->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            @include('theme-ecommerce::partials._section-heading', [
                'title' => __('Best sellers'),
                'subtitle' => __('Most loved by our customers'),
            ])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                @foreach ($bestSellers as $product)
                    @include('theme-ecommerce::partials._product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($newArrivals->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            @include('theme-ecommerce::partials._section-heading', [
                'title' => __('New arrivals'),
                'subtitle' => __('Just added to the store'),
            ])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                @foreach ($newArrivals as $product)
                    @include('theme-ecommerce::partials._product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($homeBrands->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            @include('theme-ecommerce::partials._section-heading', [
                'title' => __('Shop by brand'),
            ])
            <div class="grid grid-cols-2 gap-3 md:grid-cols-5 md:gap-4">
                @foreach ($homeBrands as $brand)
                    <a href="{{ route('shop.brand', $brand->slug) }}"
                        class="flex min-h-[64px] items-center justify-center rounded-card bg-white p-4 shadow-sm transition hover:shadow-md {{ $brand->logo ? 'grayscale hover:grayscale-0' : '' }}">
                        @if ($brand->logo)
                            <img src="{{ $brand->logo }}" alt="{{ $brand->name }}" loading="lazy" decoding="async" width="160" height="48" class="max-h-12 w-auto object-contain">
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
            @include('theme-ecommerce::partials._section-heading', ['title' => $section->name])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4">
                @foreach ($section->localizedCards() as $card)
                    <div class="group overflow-hidden rounded-card bg-white shadow-sm transition hover:shadow-md">
                        @if ($card['image'])
                            <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100">
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" decoding="async" width="800" height="600"
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

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>