<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
</head>
<body class="bg-white text-zinc-800 antialiased">

@include('frontend.themes.ecommerce.partials.header')

@php
    $facets = collect(['category', 'brand', 'tag', 'type', 'min_price', 'max_price']);
    $activeFacetCount = $facets->reject(fn ($facet) => blank($filters[$facet]))->count() + count($filters['attributes']);
    $hasActiveFilters = $activeFacetCount > 0 || filled($filters['search']);
@endphp

@include('frontend.themes.ecommerce.partials.breadcrumbs', [
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Shop'), 'url' => null],
    ],
])

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-zinc-900">{{ __('Shop') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">
            {{ __(':count products found', ['count' => $products->total()]) }}
            @if ($hasActiveFilters)
                · <a href="{{ route('shop') }}" class="font-medium text-primary hover:underline">{{ __('Clear filters') }}</a>
            @endif
        </p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[17rem_1fr]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="space-y-8 rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                <form action="{{ route('shop') }}" method="GET" role="search">
                    <label for="shop-search" class="mb-2 block text-sm font-semibold text-zinc-900">{{ __('Search') }}</label>
                    <div class="relative">
                        <input type="text" id="shop-search" name="search" value="{{ $filters['search'] }}"
                            placeholder="{{ __('Search products...') }}"
                            class="w-full rounded-xl border-zinc-200 py-2 pl-4 pr-10 text-sm">
                        <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-primary" aria-label="{{ __('Search') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                            </svg>
                        </button>
                    </div>
                </form>

                @php
                    $bounds = $priceBounds ?? (object) ['min' => 0, 'max' => 100000];
                    $priceMin = (float) $bounds->min;
                    $priceMax = (float) $bounds->max;
                    $filterMin = is_numeric($filters['min_price']) ? (float) $filters['min_price'] : $priceMin;
                    $filterMax = is_numeric($filters['max_price']) ? (float) $filters['max_price'] : $priceMax;
                    $currencySymbol = \App\Models\Setting::get('currency_symbol', '৳');
                @endphp

                <style>
                    .cw-range { appearance: none; -webkit-appearance: none; background: transparent; pointer-events: none; }
                    .cw-range::-webkit-slider-thumb { appearance: none; -webkit-appearance: none; width: 16px; height: 16px; border-radius: 9999px; background: #fff; border: 2px solid var(--color-primary); box-shadow: 0 1px 4px rgba(0,0,0,.25); pointer-events: auto; cursor: grab; }
                    .cw-range::-webkit-slider-thumb:active { cursor: grabbing; }
                    .cw-range::-moz-range-thumb { width: 16px; height: 16px; border-radius: 9999px; background: #fff; border: 2px solid var(--color-primary); box-shadow: 0 1px 4px rgba(0,0,0,.25); pointer-events: auto; cursor: grab; }
                    .cw-range::-moz-range-track { background: transparent; }
                    .cw-range:focus-visible { outline: none; }
                    .cw-range:focus-visible::-webkit-slider-thumb { outline: 2px solid color-mix(in srgb, var(--color-primary) 35%, transparent); outline-offset: 2px; }
                </style>

                <section
                    x-data="{
                        min: {{ $filterMin }},
                        max: {{ $filterMax }},
                        boundsMin: {{ $priceMin }},
                        boundsMax: {{ $priceMax }},
                        pct(v) {
                            const span = (this.boundsMax - this.boundsMin) || 1;
                            const clamped = Math.min(Math.max(v, this.boundsMin), this.boundsMax);
                            return Math.round(((clamped - this.boundsMin) / span) * 1000) / 10;
                        },
                        onMinInput() {
                            if (this.min > this.max) this.max = this.min;
                            this.min = Math.min(Math.max(this.min, this.boundsMin), this.max);
                        },
                        onMaxInput() {
                            if (this.max < this.min) this.min = this.max;
                            this.max = Math.max(Math.min(this.max, this.boundsMax), this.min);
                        },
                        setMin(v) {
                            v = Number(v) || this.boundsMin;
                            this.min = Math.min(Math.max(v, this.boundsMin), this.max);
                        },
                        setMax(v) {
                            v = Number(v) || this.boundsMax;
                            this.max = Math.max(Math.min(v, this.boundsMax), this.min);
                        },
                        format(v) { return Number(v).toLocaleString('en-US'); },
                      }"
                >
                    <h2 class="mb-3 text-sm font-semibold text-zinc-900">{{ __('Price') }}</h2>

                    <form action="{{ route('shop') }}" method="GET" class="space-y-4">
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

                        <input type="hidden" name="min_price" x-model="min">
                        <input type="hidden" name="max_price" x-model="max">

                        {{-- Min / Max inputs --}}
                        <div class="flex items-center gap-2">
                            <div class="relative w-full">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-400">{{ $currencySymbol }}</span>
                                <input type="number" min="0" step="1" :value="Math.round(min)"
                                    placeholder="{{ __('Min') }}"
                                    @change="setMin($event.target.value)"
                                    class="w-full rounded-xl border-zinc-200 py-2 pl-7 pr-3 text-sm">
                            </div>
                            <span class="text-zinc-300">&ndash;</span>
                            <div class="relative w-full">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-400">{{ $currencySymbol }}</span>
                                <input type="number" min="0" step="1" :value="Math.round(max)"
                                    placeholder="{{ __('Max') }}"
                                    @change="setMax($event.target.value)"
                                    class="w-full rounded-xl border-zinc-200 py-2 pl-7 pr-3 text-sm">
                            </div>
                        </div>

                        {{-- Dual-thumb slider --}}
                        <div class="relative h-6">
                            <div class="absolute inset-x-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-zinc-200"></div>
                            <div class="absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-primary"
                                :style="'left: ' + pct(min) + '%; right: ' + (100 - pct(max)) + '%'"></div>
                            <input type="range" :min="boundsMin" :max="boundsMax" step="1"
                                x-model.number="min" @input="onMinInput()"
                                class="cw-range absolute inset-x-0 top-1/2 z-20 h-1.5 w-full -translate-y-1/2"
                                aria-label="{{ __('Min price') }}">
                            <input type="range" :min="boundsMin" :max="boundsMax" step="1"
                                x-model.number="max" @input="onMaxInput()"
                                class="cw-range absolute inset-x-0 top-1/2 z-10 h-1.5 w-full -translate-y-1/2"
                                aria-label="{{ __('Max price') }}">
                        </div>

                        <button type="submit"
                            class="w-full rounded-full border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-600 transition hover:border-primary hover:text-primary">
                            {{ __('Apply price') }}
                        </button>
                    </form>
                </section>

                <section>
                    <h2 class="mb-3 text-sm font-semibold text-zinc-900">{{ __('Type') }}</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['physical' => __('Physical'), 'digital' => __('Digital')] as $type => $label)
                            @php
                                $selected = $filters['type'] === $type;
                            @endphp
                            <a href="{{ request()->fullUrlWithQuery(['type' => $selected ? null : $type, 'page' => null]) }}"
                                class="rounded-full border px-3 py-1 text-xs transition {{ $selected ? 'border-primary bg-primary text-white' : 'border-zinc-200 text-zinc-600 hover:border-primary hover:text-primary' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="mb-3 text-sm font-semibold text-zinc-900">{{ __('Categories') }}</h2>
                    <ul class="space-y-0.5 text-sm">
                        @foreach (\App\Models\ProductCategory::tree($categories) as $category)
                            <li>
                                <a href="{{ request()->fullUrlWithQuery(['category' => $category->slug, 'page' => null]) }}"
                                    class="flex items-center justify-between rounded-lg px-3 py-1.5 transition {{ $filters['category'] === $category->slug ? 'bg-primary/8 font-semibold text-primary' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900' }}"
                                    style="margin-left: {{ $category->depth * 0.9 }}rem">
                                    <span class="truncate">{{ $category->name }}</span>
                                    @if ($category->products_count > 0)
                                        <span class="ml-2 shrink-0 text-xs text-zinc-400">{{ $category->products_count }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($brands->isNotEmpty())
                    <section>
                        <h2 class="mb-3 text-sm font-semibold text-zinc-900">{{ __('Brands') }}</h2>
                        <ul class="space-y-0.5 text-sm">
                            @foreach ($brands as $brand)
                                <li>
                                    <a href="{{ request()->fullUrlWithQuery(['brand' => $brand->slug, 'page' => null]) }}"
                                        class="flex items-center justify-between rounded-lg px-3 py-1.5 transition {{ $filters['brand'] === $brand->slug ? 'bg-primary/8 font-semibold text-primary' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900' }}">
                                        <span class="truncate">{{ $brand->name }}</span>
                                        @if ($brand->products_count > 0)
                                            <span class="ml-2 shrink-0 text-xs text-zinc-400">{{ $brand->products_count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($tags->isNotEmpty())
                    <section>
                        <h2 class="mb-3 text-sm font-semibold text-zinc-900">{{ __('Tags') }}</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                <a href="{{ request()->fullUrlWithQuery(['tag' => $tag->slug, 'page' => null]) }}"
                                    class="rounded-full border px-3 py-1 text-xs transition {{ $filters['tag'] === $tag->slug ? 'border-primary bg-primary text-white' : 'border-zinc-200 text-zinc-600 hover:border-primary hover:text-primary' }}">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if (! empty($attributeFacets))
                    <section>
                        <h2 class="mb-3 text-sm font-semibold text-zinc-900">{{ __('Options') }}</h2>
                        @foreach ($attributeFacets as $name => $values)
                            <h3 class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-zinc-500 first:mt-0">{{ $name }}</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($values as $value => $count)
                                    @php
                                        $selected = ($filters['attributes'][$name] ?? null) === $value;
                                        $nextAttributes = $selected
                                            ? array_filter($filters['attributes'], fn ($_, $k) => $k !== $name, ARRAY_FILTER_USE_BOTH)
                                            : ($filters['attributes'] + [$name => $value]);
                                    @endphp
                                    <a href="{{ request()->fullUrlWithQuery(['attributes' => $nextAttributes, 'page' => null]) }}"
                                        class="rounded-full border px-3 py-1 text-xs transition {{ $selected ? 'border-primary bg-primary text-white' : 'border-zinc-200 text-zinc-600 hover:border-primary hover:text-primary' }}">
                                        {{ $value }}
                                        <span class="ml-1 text-[10px] {{ $selected ? 'text-white/80' : 'text-zinc-400' }}">({{ $count }})</span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </section>
                @endif
            </div>
        </aside>

        <div>
            <form action="{{ route('shop') }}" method="GET" class="mb-6 flex flex-wrap items-center justify-end gap-2">
                @if (filled($filters['category']))
                    <input type="hidden" name="category" value="{{ $filters['category'] }}">
                @endif
                @if (filled($filters['brand']))
                    <input type="hidden" name="brand" value="{{ $filters['brand'] }}">
                @endif
                @if (filled($filters['tag']))
                    <input type="hidden" name="tag" value="{{ $filters['tag'] }}">
                @endif
                @if (filled($filters['search']))
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                @endif
                @if (filled($filters['type']))
                    <input type="hidden" name="type" value="{{ $filters['type'] }}">
                @endif
                @if (filled($filters['min_price']))
                    <input type="hidden" name="min_price" value="{{ $filters['min_price'] }}">
                @endif
                @if (filled($filters['max_price']))
                    <input type="hidden" name="max_price" value="{{ $filters['max_price'] }}">
                @endif
                @foreach ($filters['attributes'] as $attrName => $attrValue)
                    <input type="hidden" name="attributes[{{ $attrName }}]" value="{{ $attrValue }}">
                @endforeach

                <label for="shop-sort" class="text-sm text-zinc-500">{{ __('Sort by') }}</label>
                <select id="shop-sort" name="sort" onchange="this.form.submit()"
                    class="rounded-xl border-zinc-200 py-2 text-sm">
                    <option value="" {{ blank($filters['sort']) ? 'selected' : '' }}>{{ __('Default') }}</option>
                    <option value="newest" {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                    <option value="price_asc" {{ $filters['sort'] === 'price_asc' ? 'selected' : '' }}>{{ __('Price: Low to High') }}</option>
                    <option value="price_desc" {{ $filters['sort'] === 'price_desc' ? 'selected' : '' }}>{{ __('Price: High to Low') }}</option>
                </select>
            </form>

            @if ($products->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        @include('frontend.themes.ecommerce.partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @else
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-200 py-24 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-4 h-12 w-12 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    <h2 class="text-lg font-semibold text-zinc-900">{{ __('No products found') }}</h2>
                    <p class="mt-1 max-w-sm text-sm text-zinc-500">{{ __('Try adjusting your search or clearing the filters.') }}</p>
                    <a href="{{ route('shop') }}" class="mt-5 rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
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
</body>
</html>