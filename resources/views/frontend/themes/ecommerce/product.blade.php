<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    @include('partials.custom-code-head')
</head>
<body class="bg-white font-storefront text-sf-text antialiased">

@include('frontend.themes.ecommerce.partials.header')

@php
    $visibleVariations = collect($product->variations ?? [])
        ->filter(fn (array $row) => $row['visible'] ?? true)
        ->values();

    $galleryImages = collect()
        ->push($product->featured_image)
        ->merge($product->gallery->pluck('url'))
        ->filter()
        ->values()
        ->all();

    $hasVariations = $visibleVariations->isNotEmpty();
    $baseDiscountLabel = $product->hasDiscount() ? format_money($product->discount_price) : null;

    $discountPercent = (! $hasVariations && $product->hasDiscount() && (float) $product->price > 0)
        ? (int) round(((float) $product->price - (float) $product->discount_price) / (float) $product->price * 100)
        : null;

    $rating = null;
    if (\App\Support\Features::enabled('reviews') && $product->averageRating() !== null) {
        $rating = [
            'average' => $product->averageRating(),
            'count' => $product->approvedReviews()->count(),
        ];
    }

    $productDetailTab = filled($product->description) ? 'description' : 'specifications';

    $crumbs = [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Shop'), 'url' => route('shop')],
    ];

    if ($category = $product->categories->first()) {
        $crumbs[] = ['label' => $category->name, 'url' => route('shop.category', $category->slug)];
    }

    $crumbs[] = ['label' => $product->name, 'url' => null];
@endphp

@include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="grid gap-8 md:grid-cols-2 lg:gap-10">
        <div class="space-y-4">
            @if ($galleryImages)
                {{-- Gallery: vertical thumbnail rail + main image with a smooth
                     hover zoom. The zoom origin eases toward the cursor on every
                     animation frame (instead of snapping to it), so panning
                     around the zoomed image glides. Touch devices skip the zoom. --}}
                <div
                    x-data="{
                        active: 0,
                        images: @js(array_values($galleryImages)),
                        zooming: false,
                        canZoom: window.matchMedia('(hover: hover) and (pointer: fine)').matches,
                        target: { x: 50, y: 50 },
                        origin: { x: 50, y: 50 },
                        frame: null,
                        show(index) {
                            this.active = (index + this.images.length) % this.images.length;
                            this.$nextTick(() => this.$refs.thumbs?.children[this.active]?.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' }));
                        },
                        enter(e) {
                            if (! this.canZoom) return;
                            this.track(e);
                            this.origin = { ...this.target };
                            this.zooming = true;
                            this.loop();
                        },
                        track(e) {
                            const r = this.$refs.stage.getBoundingClientRect();
                            this.target.x = Math.min(Math.max(((e.clientX - r.left) / r.width) * 100, 0), 100);
                            this.target.y = Math.min(Math.max(((e.clientY - r.top) / r.height) * 100, 0), 100);
                        },
                        loop() {
                            cancelAnimationFrame(this.frame);
                            const step = () => {
                                this.origin.x += (this.target.x - this.origin.x) * 0.18;
                                this.origin.y += (this.target.y - this.origin.y) * 0.18;
                                this.$refs.zoomImg.style.transformOrigin = `${this.origin.x}% ${this.origin.y}%`;
                                if (this.zooming) this.frame = requestAnimationFrame(step);
                            };
                            step();
                        },
                        leave() {
                            this.zooming = false;
                            cancelAnimationFrame(this.frame);
                        },
                    }"
                    @keydown.arrow-right.window="if ($event.target === document.body) show(active + 1)"
                    @keydown.arrow-left.window="if ($event.target === document.body) show(active - 1)"
                    class="flex flex-col-reverse gap-3 sm:flex-row"
                >
                    @if (count($galleryImages) > 1)
                        {{-- Thumbnails: a row under the image on mobile, a
                             scrolling rail as tall as the image from sm up. --}}
                        <div class="sm:relative sm:w-20 sm:shrink-0">
                            <div x-ref="thumbs" class="flex gap-2.5 overflow-x-auto pb-1 sm:absolute sm:inset-0 sm:flex-col sm:overflow-y-auto sm:overflow-x-hidden sm:pb-0 sm:pr-0.5 [scrollbar-width:thin]">
                                <template x-for="(image, index) in images" :key="index">
                                    <button type="button" @click="show(index)" @mouseenter="show(index)"
                                        :aria-label="`{{ __('Image') }} ${index + 1}`"
                                        :aria-current="active === index"
                                        :class="active === index
                                            ? 'border-brand ring-2 ring-brand/20'
                                            : 'border-zinc-200 opacity-70 hover:border-zinc-300 hover:opacity-100'"
                                        class="aspect-square w-18 shrink-0 overflow-hidden rounded-lg border-2 bg-white transition duration-200 sm:w-full">
                                        <img :src="image" alt="" loading="lazy" class="h-full w-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </div>
                    @endif

                    <div class="group/stage relative min-w-0 flex-1">
                        <div
                            x-ref="stage"
                            @mouseenter="enter($event)"
                            @mousemove="track($event)"
                            @mouseleave="leave()"
                            :class="zooming ? 'cursor-zoom-out' : (canZoom ? 'cursor-zoom-in' : '')"
                            class="relative aspect-square overflow-hidden rounded-card border border-zinc-100 bg-zinc-50 shadow-sm"
                        >
                            <template x-for="(image, index) in images" :key="index">
                                <img :src="image" alt="{{ $product->name }}"
                                    x-show="active === index"
                                    x-transition:enter="transition-opacity duration-300 ease-out"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    class="absolute inset-0 h-full w-full object-cover">
                            </template>

                            {{-- The zoom layer: the active image scaled up, its
                                 origin following the cursor. Scale eases in/out. --}}
                            <img x-ref="zoomImg" :src="images[active]" alt="" aria-hidden="true"
                                :style="zooming ? 'transform: scale(2.2); opacity: 1' : 'transform: scale(1); opacity: 0'"
                                class="pointer-events-none absolute inset-0 h-full w-full object-cover will-change-transform [transition:transform_450ms_cubic-bezier(0.22,1,0.36,1),opacity_200ms_ease-out]">

                            <span x-show="canZoom && ! zooming" x-transition.opacity
                                class="pointer-events-none absolute bottom-3 right-3 flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-zinc-600 shadow-sm backdrop-blur">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6" />
                                </svg>
                                {{ __('Hover to zoom') }}
                            </span>
                        </div>

                        @if (count($galleryImages) > 1)
                            <button type="button" @click="show(active - 1)" aria-label="{{ __('Previous image') }}"
                                class="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-700 shadow-md backdrop-blur transition hover:bg-white hover:text-brand sm:opacity-0 sm:group-hover/stage:opacity-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                            </button>
                            <button type="button" @click="show(active + 1)" aria-label="{{ __('Next image') }}"
                                class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-700 shadow-md backdrop-blur transition hover:bg-white hover:text-brand sm:opacity-0 sm:group-hover/stage:opacity-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </button>
                            <span class="pointer-events-none absolute left-3 top-3 rounded-full bg-zinc-900/70 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur"
                                x-text="`${active + 1} / ${images.length}`"></span>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex aspect-square items-center justify-center rounded-card border border-zinc-100 bg-zinc-50 text-zinc-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                    </svg>
                </div>
            @endif
        </div>

        <div class="lg:py-4">
            {{-- The name leads the column; brand / type live with the categories below. --}}
            {{-- Name, then price right under it; a status badge sits beside the
                 name (upcoming, or stock). On variant products the price, SKU and
                 stock follow the combination picked in the add-to-cart picker
                 below, which announces it with a `variant-selected` event; the
                 first combination (the picker's default) renders server-side. --}}
            @php
                $firstVariation = $visibleVariations->first();
                $variantPrice = $firstVariation['price'] ?? $product->price;
                $initial = $hasVariations
                    ? [
                        'inStock' => (int) ($firstVariation['quantity'] ?? 0) > 0,
                        'priceLabel' => format_money($variantPrice),
                        'discountLabel' => isset($firstVariation['price'], $firstVariation['discount_price'])
                            && (float) $firstVariation['discount_price'] < (float) $firstVariation['price']
                                ? format_money($firstVariation['discount_price'])
                                : null,
                        'sku' => ($firstVariation['sku'] ?? null) ?: null,
                    ]
                    : [
                        'inStock' => $product->inStock(),
                        'priceLabel' => format_money($product->price),
                        'discountLabel' => $baseDiscountLabel,
                        'sku' => $product->sku ?: null,
                    ];
            @endphp

            <div
                x-data="{
                    selected: true,
                    inStock: @js($initial['inStock']),
                    priceLabel: @js($initial['priceLabel']),
                    discountLabel: @js($initial['discountLabel']),
                    sku: @js($initial['sku']),
                }"
                @if ($hasVariations)
                    @variant-selected.window="if ($event.detail.productId === {{ $product->id }}) {
                        selected = $event.detail.selected;
                        inStock = $event.detail.inStock;
                        priceLabel = $event.detail.priceLabel;
                        discountLabel = $event.detail.discountLabel;
                        sku = $event.detail.sku;
                    }"
                @endif
            >
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <h1 class="text-2xl font-extrabold tracking-tight text-sf-heading sm:text-3xl">{{ $product->name }}</h1>

                    @if ($product->is_upcoming)
                        <span class="inline-flex shrink-0 items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ __('Upcoming') }}</span>
                    @else
                        {{-- Two fixed badges toggled with x-show (no class juggling). --}}
                        <span x-show="selected && inStock" @unless ($initial['inStock']) x-cloak @endunless
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                            {{ __('In stock') }}
                        </span>
                        <span x-show="selected && ! inStock" @if ($initial['inStock']) x-cloak @endif
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-600 ring-1 ring-inset ring-red-600/20">
                            <span class="size-1.5 rounded-full bg-red-500"></span>
                            {{ __('Out of stock') }}
                        </span>
                    @endif
                </div>

                <p x-show="sku" @unless ($initial['sku']) x-cloak @endunless class="mt-1 text-sm text-zinc-500">
                    {{ __('SKU') }}: <span class="font-mono" x-text="sku">{{ $initial['sku'] }}</span>
                </p>

                <div class="mt-4 flex items-baseline gap-3">
                    <span class="text-2xl font-extrabold text-sf-price sm:text-3xl" x-text="discountLabel || priceLabel">{{ $initial['discountLabel'] ?: $initial['priceLabel'] }}</span>
                    <span x-show="discountLabel" @unless ($initial['discountLabel']) x-cloak @endunless
                        class="text-lg text-zinc-400 line-through" x-text="priceLabel">{{ $initial['priceLabel'] }}</span>
                    @if ($discountPercent !== null)
                        <span class="rounded-full bg-brand/10 px-2.5 py-1 text-xs font-bold text-brand">-{{ $discountPercent }}%</span>
                    @endif
                </div>

                @if ($rating)
                    <a href="#reviews" class="mt-3 inline-flex items-center gap-2 text-sm text-zinc-500 transition hover:text-sf-heading">
                        <span class="flex gap-0.5">
                            @foreach (range(1, 5) as $i)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                    class="h-4 w-4 {{ $i <= round($rating['average']) ? 'fill-amber-400 text-amber-400' : 'fill-zinc-200 text-zinc-200' }}"
                                    stroke="currentColor" stroke-width="1">
                                    <path stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                </svg>
                            @endforeach
                        </span>
                        <span class="font-semibold text-sf-heading">{{ number_format($rating['average'], 1) }}</span>
                        <span>({{ trans_choice(':count review|:count reviews', $rating['count'], ['count' => $rating['count']]) }})</span>
                    </a>
                @endif
            </div>

            {{-- The option picker lives inside the add-to-cart component below, so the
                 *picked* combination is what actually lands in the cart line. --}}

            @if ($product->warranty_months > 0 || ! $product->charge_shipping)
            <div class="mt-6 flex flex-wrap gap-2 text-sm text-zinc-600">
                @if ($product->warranty_months > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 px-3 py-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        {{ __(':months months warranty', ['months' => $product->warranty_months]) }}
                    </span>
                @endif

                {{-- Only free shipping is worth calling out; charged shipping shows at checkout. --}}
                @unless ($product->charge_shipping)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 px-3 py-1.5">
                        <span class="font-medium text-emerald-600">{{ __('Free shipping') }}</span>
                    </span>
                @endunless
            </div>
            @endif

            <div class="mt-6">
                <livewire:frontend.add-to-cart-button
                    :product-id="$product->id"
                    :adjustable="true"
                    :quantity="1"
                    :show-picker="$hasVariations"
                    :key="'add-to-cart-'.$product->id"
                />
            </div>

            @if (filled($product->excerpt))
                <div class="mt-8 border-t border-zinc-100 pt-6 text-zinc-600">
                    <div class="rich-text leading-relaxed">{!! $product->excerpt !!}</div>
                </div>
            @endif

            {{-- Product details as plain value pills — categories, brand, type,
                 then any tags — no labels in front of them. --}}
            <div class="mt-8 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-6">
                @foreach ($product->categories as $category)
                    <a href="{{ route('shop.category', $category->slug) }}"
                        class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-medium text-zinc-600 transition hover:border-brand hover:text-brand">
                        {{ $category->name }}
                    </a>
                @endforeach

                @if ($product->brand)
                    <a href="{{ route('shop.brand', $product->brand->slug) }}"
                        class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-zinc-600 transition hover:bg-brand/10 hover:text-brand">
                        {{ $product->brand->name }}
                    </a>
                @endif

                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-500">
                    {{ $product->product_type === 'digital' ? __('Digital') : __('Physical') }}
                </span>

                @foreach ($product->tags as $tag)
                    <a href="{{ route('shop.tag', $tag->slug) }}"
                        class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 transition hover:bg-brand/10 hover:text-brand">
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @php($hasProductDetails = filled($product->description) || filled($product->specifications))

    @if ($hasProductDetails || $advertisement)
    {{-- Details tabs on the left, the sponsored banner beside them on large screens. --}}
    <div @class([
        'mt-10 grid items-start gap-8 sm:mt-14 lg:gap-10',
        'lg:grid-cols-5' => $hasProductDetails && $advertisement,
    ])>
    @if ($hasProductDetails)
        <section @class(['min-w-0', 'lg:col-span-3' => $advertisement, 'max-w-3xl' => ! $advertisement])>
            <div x-data="{ tab: @js($productDetailTab) }">
                <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ __('Product details') }}">
                    @if (filled($product->description))
                        <button type="button" role="tab" aria-selected="true" @click="tab = 'description'"
                            :aria-selected="tab === 'description'"
                            :class="tab === 'description' ? 'bg-brand text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-brand/10 hover:text-brand'"
                            class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            {{ __('Description') }}
                        </button>
                    @endif

                    @if (filled($product->specifications))
                        <button type="button" role="tab" aria-selected="false" @click="tab = 'specifications'"
                            :aria-selected="tab === 'specifications'"
                            :class="tab === 'specifications' ? 'bg-brand text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-brand/10 hover:text-brand'"
                            class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                            </svg>
                            {{ __('Specifications') }}
                        </button>
                    @endif
                </div>

                <div class="mt-6 rounded-card border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
                    @if (filled($product->description))
                        <div x-show="tab === 'description'" @if ($productDetailTab !== 'description') x-cloak @endif role="tabpanel"
                            class="rich-text text-base leading-relaxed text-zinc-600">
                            {!! $product->description !!}
                        </div>
                    @endif

                    @if (filled($product->specifications))
                        <div x-show="tab === 'specifications'" @if ($productDetailTab !== 'specifications') x-cloak @endif role="tabpanel"
                            class="rich-text text-sm leading-relaxed text-zinc-600">
                            {!! $product->specifications !!}
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if ($advertisement)
        <aside @class(['min-w-0', 'lg:col-span-2 lg:sticky lg:top-24' => $hasProductDetails, 'mx-auto w-full max-w-5xl' => ! $hasProductDetails])>
            <a href="{{ route('advertisements.click', $advertisement->code) }}" target="_blank"
                rel="noopener noreferrer nofollow" class="group block overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                <span class="relative block">
                    @if ($advertisement->image)
                        <img src="{{ $advertisement->image }}" alt="{{ $advertisement->name }}" loading="lazy"
                            @class([
                                'w-full object-cover transition duration-500 group-hover:scale-[1.02]',
                                $hasProductDetails ? 'aspect-[16/9] lg:aspect-[4/3]' : 'aspect-[21/8]',
                            ]) />
                    @endif
                    <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-600 shadow-sm backdrop-blur">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 0-.59-4.59c.18-1.964.597-3.88 1.227-5.68M10.34 15.84a18.046 18.046 0 0 0 3.51.659c3.563.06 6.744 1.507 8.505 3.832.15.195.238.434.256.679.04.574-.387 1.108-.96 1.155-2.129.11-4.899.362-7.707.511M7.5 11.999h.01" />
                        </svg>
                        {{ __('Sponsored') }}
                    </span>
                </span>
                @if ($advertisement->name || $advertisement->url)
                    <span class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 px-5 py-4 sm:px-6">
                        @if ($advertisement->name)
                            <span class="text-sm font-semibold text-sf-heading">{{ $advertisement->name }}</span>
                        @endif
                        @if ($advertisement->url)
                            <span class="text-xs font-medium text-sf-primary">
                                {{ __('Learn more') }} <span aria-hidden="true">→</span>
                                @if ($host = parse_url($advertisement->url, PHP_URL_HOST))
                                    <span class="font-normal text-zinc-400">{{ $host }}</span>
                                @endif
                            </span>
                        @endif
                    </span>
                @endif
            </a>
        </aside>
    @endif
    </div>
    @endif

    {{-- Reviews: approved ones for everyone; the form only for buyers. --}}
    @if (\App\Support\Features::enabled('reviews'))
        <livewire:frontend.product-reviews :product-id="$product->id" :key="'product-reviews-'.$product->id" />
    @endif

    @if ($product->faqs->where('is_active', true)->isNotEmpty())
        <section class="mt-14 max-w-3xl">
            <h2 class="mb-5 text-2xl font-bold text-sf-heading">{{ __('Frequently asked questions') }}</h2>
            <div class="space-y-3">
                @foreach ($product->faqs->where('is_active', true) as $faq)
                    <details class="group rounded-card border border-zinc-100 bg-white shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-medium text-sf-heading">
                            {{ $faq->question }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-zinc-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-5 pb-5 leading-relaxed text-zinc-600">{{ $faq->answer }}</div>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="mx-auto max-w-7xl border-t border-zinc-100 py-12">
            <h2 class="mb-6 text-2xl font-bold text-sf-heading">{{ $section->name }}</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-5 lg:grid-cols-4">
                @foreach ($section->localizedCards() as $card)
                    <div class="group overflow-hidden rounded-card border border-zinc-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        @if ($card['image'])
                            <div class="relative aspect-square overflow-hidden bg-zinc-100">
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="p-4">
                            @if ($card['title'])
                                <h3 class="font-semibold text-sf-heading">{{ $card['title'] }}</h3>
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

    @if ($related->isNotEmpty())
        <section class="mx-auto max-w-7xl border-t border-zinc-100 py-12">
            <h2 class="mb-6 text-2xl font-bold text-sf-heading">{{ __('You may also like') }}</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-5 lg:grid-cols-4">
                @foreach ($related as $product)
                    @include('frontend.themes.ecommerce.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>