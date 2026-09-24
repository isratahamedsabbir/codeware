<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    @include('partials.custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('frontend.themes.ecommerce.partials.header')

@php
    $facets = collect(['category', 'brand', 'tag', 'type', 'min_price', 'max_price']);
    $activeFacetCount = $facets->reject(fn ($facet) => blank($filters[$facet]))->count() + count($filters['attributes']);
    $hasActiveFilters = $activeFacetCount > 0 || filled($filters['search']);

    $bounds = $priceBounds ?? (object) ['min' => 0, 'max' => 100000];
    $priceMin = floor((float) $bounds->min);
    $priceMax = ceil((float) $bounds->max);
    $filterMin = is_numeric($filters['min_price']) ? (float) $filters['min_price'] : $priceMin;
    $filterMax = is_numeric($filters['max_price']) ? (float) $filters['max_price'] : $priceMax;
    $currencySymbol = \App\Models\Setting::get('currency_symbol', '৳');

    // Removable chips for every active filter — each links to the current URL
    // minus just that one filter.
    $without = fn (array $keys) => request()->fullUrlWithQuery(array_fill_keys([...$keys, 'page'], null));
    $chips = [];
    if (filled($filters['search'])) {
        $chips[] = ['label' => __('Search').': '.$filters['search'], 'url' => $without(['search'])];
    }
    if (filled($filters['category'])) {
        $chips[] = ['label' => $categories->firstWhere('slug', $filters['category'])?->name ?? $filters['category'], 'url' => $without(['category'])];
    }
    if (filled($filters['brand'])) {
        $chips[] = ['label' => $brands->firstWhere('slug', $filters['brand'])?->name ?? $filters['brand'], 'url' => $without(['brand'])];
    }
    if (filled($filters['tag'])) {
        $chips[] = ['label' => '#'.($tags->firstWhere('slug', $filters['tag'])?->name ?? $filters['tag']), 'url' => $without(['tag'])];
    }
    if (filled($filters['type'])) {
        $chips[] = ['label' => $filters['type'] === 'digital' ? __('Digital') : __('Physical'), 'url' => $without(['type'])];
    }
    if (filled($filters['min_price']) || filled($filters['max_price'])) {
        $chips[] = ['label' => $currencySymbol.number_format($filterMin).' – '.$currencySymbol.number_format($filterMax), 'url' => $without(['min_price', 'max_price'])];
    }
    foreach ($filters['attributes'] as $attrName => $attrValue) {
        $chips[] = [
            'label' => $attrName.': '.$attrValue,
            'url' => request()->fullUrlWithQuery(['attributes' => array_diff_key($filters['attributes'], [$attrName => true]) ?: null, 'page' => null]),
        ];
    }

    $sectionSummary = 'flex cursor-pointer list-none items-center justify-between py-4 text-sm font-bold text-sf-heading [&::-webkit-details-marker]:hidden';
    $chevron = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-400 transition-transform duration-200 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>';
    $pill = fn (bool $on) => 'rounded-full border px-3 py-1.5 text-xs font-medium transition '.($on
        ? 'border-brand bg-brand text-white shadow-sm'
        : 'border-zinc-200 bg-white text-zinc-600 hover:border-brand hover:text-brand');
    $listRow = fn (bool $on) => 'group/row flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition '.($on
        ? 'bg-brand/8 font-semibold text-brand'
        : 'text-zinc-600 hover:bg-zinc-50 hover:text-sf-heading');
    $radio = fn (bool $on) => 'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition '.($on
        ? 'border-brand'
        : 'border-zinc-300 group-hover/row:border-zinc-400');
@endphp

@include('frontend.themes.ecommerce.partials.breadcrumbs', [
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Shop'), 'url' => null],
    ],
])

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-sf-heading md:text-3xl">{{ __('Shop') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('Browse our full collection and narrow it down with the filters.') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[17.5rem_1fr]" x-data="{ filtersOpen: false }">
        {{-- Mobile filter toggle --}}
        <div class="lg:hidden">
            <button
                type="button"
                @click="filtersOpen = !filtersOpen"
                :aria-expanded="filtersOpen"
                aria-controls="shop-filters"
                class="flex w-full items-center justify-between rounded-card border border-zinc-200 bg-white px-4 py-3 text-sm font-semibold text-sf-heading shadow-sm transition hover:border-brand"
            >
                <span class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    {{ __('Filters') }}
                    @if ($activeFacetCount > 0)
                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-sf-button px-1.5 text-[11px] font-bold text-sf-button-text">{{ $activeFacetCount }}</span>
                    @endif
                </span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-400 transition-transform duration-200" :class="filtersOpen ? '-rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                </svg>
            </button>
        </div>

        <aside id="shop-filters" class="lg:sticky lg:top-24 lg:self-start" :class="filtersOpen ? 'block' : 'hidden lg:block'">
            <div class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                {{-- Panel header --}}
                <div class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                    <h2 class="flex items-center gap-2 text-base font-bold text-sf-heading">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                        </svg>
                        {{ __('Filters') }}
                        @if ($activeFacetCount > 0)
                            <span class="rounded-full bg-brand/10 px-2 py-0.5 text-xs font-bold text-brand">{{ $activeFacetCount }}</span>
                        @endif
                    </h2>
                    @if ($hasActiveFilters)
                        <a href="{{ route('shop') }}" class="text-xs font-semibold text-zinc-500 underline-offset-4 transition hover:text-red-600 hover:underline">{{ __('Clear all') }}</a>
                    @endif
                </div>

                <div class="divide-y divide-zinc-100 px-5">
                    {{-- Search --}}
                    <form action="{{ route('shop') }}" method="GET" role="search" class="py-4">
                        <label for="shop-search" class="sr-only">{{ __('Search') }}</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                            </span>
                            <input type="search" id="shop-search" name="search" value="{{ $filters['search'] }}"
                                placeholder="{{ __('Search products...') }}"
                                class="w-full rounded-xl border border-zinc-300 bg-white py-2.5 pl-10 pr-4 text-sm text-sf-text shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
                        </div>
                    </form>

                    {{-- Price — applies on its own shortly after the shopper lets go of
                         a slider thumb or edits a number; no Apply button. --}}
                    <style>
                        .cw-range { appearance: none; -webkit-appearance: none; background: transparent; pointer-events: none; }
                        .cw-range::-webkit-slider-thumb { appearance: none; -webkit-appearance: none; width: 18px; height: 18px; border-radius: 9999px; background: #fff; border: 2px solid var(--color-brand); box-shadow: 0 1px 4px rgba(0,0,0,.2); pointer-events: auto; cursor: grab; transition: transform .15s; }
                        .cw-range::-webkit-slider-thumb:hover { transform: scale(1.12); }
                        .cw-range::-webkit-slider-thumb:active { cursor: grabbing; }
                        .cw-range::-moz-range-thumb { width: 18px; height: 18px; border-radius: 9999px; background: #fff; border: 2px solid var(--color-brand); box-shadow: 0 1px 4px rgba(0,0,0,.2); pointer-events: auto; cursor: grab; }
                        .cw-range::-moz-range-track { background: transparent; }
                        .cw-range:focus-visible { outline: none; }
                        .cw-range:focus-visible::-webkit-slider-thumb { outline: 3px solid color-mix(in srgb, var(--color-brand) 30%, transparent); outline-offset: 1px; }
                    </style>

                    <details open class="group">
                        <summary class="{{ $sectionSummary }}">{{ __('Price') }} {!! $chevron !!}</summary>
                        <section
                            class="pb-5"
                            x-data="{
                                min: {{ $filterMin }},
                                max: {{ $filterMax }},
                                appliedMin: {{ $filterMin }},
                                appliedMax: {{ $filterMax }},
                                boundsMin: {{ $priceMin }},
                                boundsMax: {{ $priceMax }},
                                timer: null,
                                applying: false,
                                pct(v) {
                                    const span = (this.boundsMax - this.boundsMin) || 1;
                                    const clamped = Math.min(Math.max(v, this.boundsMin), this.boundsMax);
                                    return Math.round(((clamped - this.boundsMin) / span) * 1000) / 10;
                                },
                                onMinInput() { if (this.min > this.max) this.min = this.max; },
                                onMaxInput() { if (this.max < this.min) this.max = this.min; },
                                setMin(v) {
                                    v = v === '' ? this.boundsMin : Number(v);
                                    this.min = Math.min(Math.max(isNaN(v) ? this.boundsMin : v, this.boundsMin), this.max);
                                    this.apply();
                                },
                                setMax(v) {
                                    v = v === '' ? this.boundsMax : Number(v);
                                    this.max = Math.max(Math.min(isNaN(v) ? this.boundsMax : v, this.boundsMax), this.min);
                                    this.apply();
                                },
                                apply() {
                                    clearTimeout(this.timer);
                                    this.timer = setTimeout(() => {
                                        if (this.min === this.appliedMin && this.max === this.appliedMax) return;
                                        this.applying = true;
                                        this.$refs.priceForm.requestSubmit();
                                    }, 450);
                                },
                                format(v) { return Number(v).toLocaleString('en-US'); },
                            }"
                        >
                            <form x-ref="priceForm" action="{{ route('shop') }}" method="GET" class="space-y-4">
                                @foreach (request()->query() as $key => $value)
                                    @unless (in_array($key, ['min_price', 'max_price', 'page'], true))
                                        @if (is_array($value))
                                            @foreach ($value as $subKey => $subValue)
                                                <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                                            @endforeach
                                        @else
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endunless
                                @endforeach

                                {{-- A thumb resting on its bound drops out of the URL, so
                                     dragging back to the full range clears the filter. --}}
                                <input type="hidden" name="min_price" :value="Math.round(min)" :disabled="min <= boundsMin">
                                <input type="hidden" name="max_price" :value="Math.round(max)" :disabled="max >= boundsMax">

                                <div class="relative h-6 px-1">
                                    <div class="absolute inset-x-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-zinc-200"></div>
                                    <div class="absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-brand"
                                        :style="'left: ' + pct(min) + '%; right: ' + (100 - pct(max)) + '%'"></div>
                                    <input type="range" :min="boundsMin" :max="boundsMax" step="1"
                                        x-model.number="min" @input="onMinInput()" @change="apply()"
                                        class="cw-range absolute inset-x-0 top-1/2 z-20 h-1.5 w-full -translate-y-1/2"
                                        aria-label="{{ __('Min price') }}">
                                    <input type="range" :min="boundsMin" :max="boundsMax" step="1"
                                        x-model.number="max" @input="onMaxInput()" @change="apply()"
                                        class="cw-range absolute inset-x-0 top-1/2 z-10 h-1.5 w-full -translate-y-1/2"
                                        aria-label="{{ __('Max price') }}">
                                </div>

                                <div class="flex items-center gap-2">
                                    <label class="relative w-full">
                                        <span class="pointer-events-none absolute left-3 top-1.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('Min') }}</span>
                                        <span class="pointer-events-none absolute bottom-2 left-3 text-sm text-zinc-500">{{ $currencySymbol }}</span>
                                        <input type="number" min="0" step="1" :value="Math.round(min)"
                                            @change="setMin($event.target.value)" @keydown.enter.prevent="setMin($event.target.value)"
                                            class="w-full rounded-xl border border-zinc-300 bg-white pb-1.5 pl-7 pr-2 pt-5 text-sm font-semibold text-sf-text shadow-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    </label>
                                    <span class="text-zinc-300">&ndash;</span>
                                    <label class="relative w-full">
                                        <span class="pointer-events-none absolute left-3 top-1.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('Max') }}</span>
                                        <span class="pointer-events-none absolute bottom-2 left-3 text-sm text-zinc-500">{{ $currencySymbol }}</span>
                                        <input type="number" min="0" step="1" :value="Math.round(max)"
                                            @change="setMax($event.target.value)" @keydown.enter.prevent="setMax($event.target.value)"
                                            class="w-full rounded-xl border border-zinc-300 bg-white pb-1.5 pl-7 pr-2 pt-5 text-sm font-semibold text-sf-text shadow-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                    </label>
                                </div>

                                <p class="flex h-4 items-center gap-1.5 text-xs text-zinc-400">
                                    <template x-if="applying">
                                        <span class="flex items-center gap-1.5 font-medium text-brand">
                                            <svg class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                                            </svg>
                                            {{ __('Updating results...') }}
                                        </span>
                                    </template>
                                    <template x-if="!applying">
                                        <span>{{ __('Range') }}: {{ $currencySymbol }}<span x-text="format(boundsMin)"></span> – {{ $currencySymbol }}<span x-text="format(boundsMax)"></span></span>
                                    </template>
                                </p>
                            </form>
                        </section>
                    </details>

                    {{-- Categories --}}
                    <details open class="group">
                        <summary class="{{ $sectionSummary }}">{{ __('Categories') }} {!! $chevron !!}</summary>
                        <ul class="-mx-2.5 max-h-72 space-y-0.5 overflow-y-auto pb-4">
                            <li>
                                <a href="{{ $without(['category']) }}" class="{{ $listRow(blank($filters['category'])) }}">
                                    <span class="{{ $radio(blank($filters['category'])) }}">
                                        @if (blank($filters['category']))<span class="h-2 w-2 rounded-full bg-brand"></span>@endif
                                    </span>
                                    <span class="truncate">{{ __('All categories') }}</span>
                                </a>
                            </li>
                            @foreach (\App\Models\ProductCategory::tree($categories) as $category)
                                @php $on = $filters['category'] === $category->slug; @endphp
                                <li>
                                    <a href="{{ request()->fullUrlWithQuery(['category' => $on ? null : $category->slug, 'page' => null]) }}"
                                        class="{{ $listRow($on) }}"
                                        style="margin-left: {{ $category->depth * 0.9 }}rem">
                                        <span class="{{ $radio($on) }}">
                                            @if ($on)<span class="h-2 w-2 rounded-full bg-brand"></span>@endif
                                        </span>
                                        <span class="truncate">{{ $category->name }}</span>
                                        @if ($category->products_count > 0)
                                            <span class="ml-auto shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-500">{{ $category->products_count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>

                    {{-- Brands --}}
                    @if ($brands->isNotEmpty())
                        <details open class="group">
                            <summary class="{{ $sectionSummary }}">{{ __('Brands') }} {!! $chevron !!}</summary>
                            <ul class="-mx-2.5 max-h-60 space-y-0.5 overflow-y-auto pb-4">
                                @foreach ($brands as $brand)
                                    @php $on = $filters['brand'] === $brand->slug; @endphp
                                    <li>
                                        <a href="{{ request()->fullUrlWithQuery(['brand' => $on ? null : $brand->slug, 'page' => null]) }}"
                                            class="{{ $listRow($on) }}">
                                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 transition {{ $on ? 'border-brand bg-brand text-white' : 'border-zinc-300 group-hover/row:border-zinc-400' }}">
                                                @if ($on)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                @endif
                                            </span>
                                            <span class="truncate">{{ $brand->name }}</span>
                                            @if ($brand->products_count > 0)
                                                <span class="ml-auto shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-500">{{ $brand->products_count }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    {{-- Type --}}
                    <details open class="group">
                        <summary class="{{ $sectionSummary }}">{{ __('Type') }} {!! $chevron !!}</summary>
                        <div class="flex flex-wrap gap-2 pb-5">
                            @foreach (['physical' => __('Physical'), 'digital' => __('Digital')] as $type => $label)
                                @php $on = $filters['type'] === $type; @endphp
                                <a href="{{ request()->fullUrlWithQuery(['type' => $on ? null : $type, 'page' => null]) }}" class="{{ $pill($on) }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </details>

                    {{-- Variation options --}}
                    @if (! empty($attributeFacets))
                        @foreach ($attributeFacets as $name => $values)
                            <details open class="group">
                                <summary class="{{ $sectionSummary }}">{{ $name }} {!! $chevron !!}</summary>
                                <div class="flex flex-wrap gap-2 pb-5">
                                    @foreach ($values as $value => $count)
                                        @php
                                            $on = ($filters['attributes'][$name] ?? null) === $value;
                                            $nextAttributes = $on
                                                ? array_filter($filters['attributes'], fn ($_, $k) => $k !== $name, ARRAY_FILTER_USE_BOTH)
                                                : ($filters['attributes'] + [$name => $value]);
                                        @endphp
                                        <a href="{{ request()->fullUrlWithQuery(['attributes' => $nextAttributes, 'page' => null]) }}" class="{{ $pill($on) }}">
                                            {{ $value }}
                                            <span class="ml-0.5 text-[10px] {{ $on ? 'text-white/80' : 'text-zinc-400' }}">({{ $count }})</span>
                                        </a>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    @endif

                    {{-- Tags --}}
                    @if ($tags->isNotEmpty())
                        <details open class="group">
                            <summary class="{{ $sectionSummary }}">{{ __('Tags') }} {!! $chevron !!}</summary>
                            <div class="flex flex-wrap gap-2 pb-5">
                                @foreach ($tags as $tag)
                                    @php $on = $filters['tag'] === $tag->slug; @endphp
                                    <a href="{{ request()->fullUrlWithQuery(['tag' => $on ? null : $tag->slug, 'page' => null]) }}" class="{{ $pill($on) }}">#{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            {{-- Results toolbar --}}
            <div class="mb-5 rounded-card border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-zinc-500">
                        @if ($products->total() > 0)
                            {!! __('Showing :from–:to of :total products', [
                                'from' => '<span class="font-semibold text-sf-heading">'.$products->firstItem().'</span>',
                                'to' => '<span class="font-semibold text-sf-heading">'.$products->lastItem().'</span>',
                                'total' => '<span class="font-semibold text-sf-heading">'.$products->total().'</span>',
                            ]) !!}
                        @else
                            {{ __(':count products found', ['count' => 0]) }}
                        @endif
                    </p>

                    <form action="{{ route('shop') }}" method="GET" class="flex items-center gap-2">
                        @foreach (request()->query() as $key => $value)
                            @unless (in_array($key, ['sort', 'page'], true))
                                @if (is_array($value))
                                    @foreach ($value as $subKey => $subValue)
                                        <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endunless
                        @endforeach

                        <label for="shop-sort" class="text-sm text-zinc-500">{{ __('Sort by') }}</label>
                        <div class="relative">
                            <select id="shop-sort" name="sort" onchange="this.form.submit()"
                                class="appearance-none rounded-xl border border-zinc-300 bg-white py-2 pl-3.5 pr-9 text-sm font-semibold text-sf-text shadow-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
                                <option value="" {{ blank($filters['sort']) ? 'selected' : '' }}>{{ __('Default') }}</option>
                                <option value="newest" {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                                <option value="price_asc" {{ $filters['sort'] === 'price_asc' ? 'selected' : '' }}>{{ __('Price: Low to High') }}</option>
                                <option value="price_desc" {{ $filters['sort'] === 'price_desc' ? 'selected' : '' }}>{{ __('Price: High to Low') }}</option>
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                            </svg>
                        </div>
                    </form>
                </div>

                @if ($chips)
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-3">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Active') }}:</span>
                        @foreach ($chips as $chip)
                            <a href="{{ $chip['url'] }}" title="{{ __('Remove filter') }}"
                                class="group/chip inline-flex items-center gap-1.5 rounded-full border border-brand/20 bg-brand/5 py-1 pl-3 pr-2 text-xs font-semibold text-brand transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                                {{ $chip['label'] }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-70 group-hover/chip:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endforeach
                        <a href="{{ route('shop') }}" class="ml-1 text-xs font-semibold text-zinc-500 underline-offset-4 hover:text-red-600 hover:underline">{{ __('Clear all') }}</a>
                    </div>
                @endif
            </div>

            @if ($products->isNotEmpty())
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        @include('frontend.themes.ecommerce.partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @else
                <div class="flex flex-col items-center justify-center rounded-card border border-dashed border-zinc-300 bg-white py-20 text-center shadow-sm">
                    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand/10 text-brand">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>
                    <h2 class="text-lg font-bold text-sf-heading">{{ __('No products found') }}</h2>
                    <p class="mt-1 max-w-sm text-sm text-zinc-500">{{ __('Try adjusting your search or clearing the filters.') }}</p>
                    <a href="{{ route('shop') }}" class="mt-5 rounded-full bg-sf-button px-6 py-2.5 text-sm font-semibold text-sf-button-text shadow-sm transition hover:opacity-90">
                        {{ __('View all products') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>
