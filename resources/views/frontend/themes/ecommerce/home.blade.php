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
    // Hero slider (Theme Settings → Banners): each slide {image, title,
    // description, url}; older image-only data still renders.
    $heroSlides = \App\Support\HeroSlides::forStorefront();
    $promoImage1 = \App\Models\Setting::get('home_promo_banner_1');
    $promoImage2 = \App\Models\Setting::get('home_promo_banner_2');

    // Promo tile links (Theme Settings): a site path or an http(s) URL —
    // anything else (blank, javascript:, …) falls back to the Shop page.
    $promoLink = fn (string $key): string => \App\Support\HeroSlides::safeUrl(\App\Models\Setting::get($key));

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
        ->take(20);

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
                class="group/hero relative block h-[300px] overflow-hidden rounded-card lg:col-span-2 lg:h-full">
                @if ($heroSlides !== [])
                    @foreach ($heroSlides as $i => $slide)
                        @php $hasText = filled($slide['title']) || filled($slide['description']); @endphp
                        <div
                            @if (count($heroSlides) > 1)
                                :class="active === {{ $i }} ? '!opacity-100 z-[1]' : 'opacity-0'"
                                :aria-hidden="active !== {{ $i }}"
                            @endif
                            class="absolute inset-0 transition-opacity duration-1000 ease-out {{ $i === 0 ? '' : 'opacity-0' }}">
                            <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] ?: $siteName }}" @if ($i > 0) loading="lazy" @endif
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
                    <div class="flex h-full w-full items-center bg-gradient-to-br from-brand to-emerald-800 px-8 md:px-12">
                    </div>
                @endif
                {{-- The hero is image-only; this keeps the page's main heading for
                     search engines and screen readers. --}}
                <h1 class="sr-only">{{ $siteName }}</h1>
            </a>

            <div class="grid grid-cols-2 gap-4 lg:flex lg:flex-col">
                @foreach ([
                    ['image' => $promoImage1, 'label' => __('New arrivals'), 'url' => $promoLink('theme_ecommerce_promo_1_link')],
                    ['image' => $promoImage2, 'label' => __('Best deals'), 'url' => $promoLink('theme_ecommerce_promo_2_link')],
                ] as $promo)
                    <a href="{{ $promo['url'] }}" class="group/promo relative block h-32 overflow-hidden rounded-card sm:h-44 lg:h-1/2">
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

                {{-- A snap-scrolling row: 3 / 5 / 6 tiles per view. The arrows only
                     appear once the tiles overflow, and fade out at either end.
                     While it overflows it also auto-advances one tile every 3s
                     (looping back to the start), pausing on hover / touch, in a
                     background tab, and for visitors who prefer reduced motion. --}}
                <div class="group/cats relative" aria-label="{{ __('Shop by category') }}" role="region"
                    x-data="{
                        canPrev: false,
                        canNext: false,
                        update() {
                            const t = this.$refs.track;
                            this.canPrev = t.scrollLeft > 4;
                            this.canNext = t.scrollLeft + t.clientWidth < t.scrollWidth - 4;
                        },
                        page(dir) {
                            const t = this.$refs.track;
                            t.scrollBy({ left: dir * t.clientWidth, behavior: 'smooth' });
                        },
                        paused: false,
                        timer: null,
                        step() {
                            const t = this.$refs.track;
                            if (this.paused || document.hidden || ! (this.canPrev || this.canNext)) return;
                            if (! this.canNext) {
                                t.scrollTo({ left: 0, behavior: 'smooth' });
                                return;
                            }
                            const tile = t.firstElementChild;
                            const gap = parseFloat(getComputedStyle(t).columnGap) || 0;
                            t.scrollBy({ left: (tile ? tile.offsetWidth : t.clientWidth) + gap, behavior: 'smooth' });
                        },
                        autoplay() {
                            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                            this.timer = setInterval(() => this.step(), 3000);
                        },
                    }"
                    x-init="update(); new ResizeObserver(() => update()).observe($refs.track); autoplay()"
                    @mouseenter="paused = true" @mouseleave="paused = false"
                    @touchstart.passive="paused = true" @touchend.passive="setTimeout(() => paused = false, 4000)"
                    @focusin="paused = true" @focusout="paused = false">
                    <div x-ref="track" @scroll.passive="update()"
                        class="-mx-1 flex snap-x snap-mandatory gap-2.5 overflow-x-auto scroll-smooth px-1 py-1.5 md:gap-4 no-scrollbar">
                        @foreach ($homeCategories as $category)
                            <a href="{{ route('shop.category', $category->slug) }}"
                                class="flex h-[104px] w-[calc((100%-1.25rem)/3)] shrink-0 snap-start flex-col items-center justify-center gap-1.5 rounded-card bg-white p-2.5 text-center shadow-sm transition hover:shadow-md md:h-[131px] md:w-[calc((100%-4rem)/5)] md:p-3 lg:w-[calc((100%-5rem)/6)] border border-zinc-200/80">
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

                    <button type="button" x-show="canPrev" x-transition.opacity x-cloak @click="page(-1)" aria-label="{{ __('Previous categories') }}"
                        class="absolute left-0 top-1/2 z-10 hidden h-10 w-10 -translate-x-1/2 md:flex -translate-y-1/2 items-center justify-center rounded-full! border border-zinc-200 bg-white text-sf-text shadow-md transition hover:border-brand hover:text-brand">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    </button>
                    <button type="button" x-show="canNext" x-transition.opacity x-cloak @click="page(1)" aria-label="{{ __('Next categories') }}"
                        class="absolute right-0 top-1/2 z-10 hidden h-10 w-10 translate-x-1/2 md:flex -translate-y-1/2 items-center justify-center rounded-full! border border-zinc-200 bg-white text-sf-text shadow-md transition hover:border-brand hover:text-brand">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </button>
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