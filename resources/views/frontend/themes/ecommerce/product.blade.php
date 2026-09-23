<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
</head>
<body class="bg-white text-zinc-800 antialiased">

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
    $baseStockLabel = $product->inStock() ? __('In stock') : __('Out of stock');

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
    <div class="grid gap-10 lg:grid-cols-2">
        <div class="space-y-4">
            @if ($galleryImages)
                <div x-data="{ active: 0, images: @js($galleryImages) }" class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm">
                    <div class="relative aspect-square overflow-hidden bg-zinc-50">
                        <img :src="images[active]" alt="{{ $product->name }}"
                            class="h-full w-full object-cover">
                    </div>
                    @if (count($galleryImages) > 1)
                        <div class="flex gap-3 overflow-x-auto border-t border-zinc-100 p-3">
                            <template x-for="(image, index) in images" :key="index">
                                <button type="button" @click="active = index"
                                    :class="active === index ? 'ring-2 ring-primary ring-offset-2' : 'opacity-60 hover:opacity-100'"
                                    class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-zinc-200 bg-white">
                                    <img :src="image" :alt="`{{ __('Image') }} ${index + 1}`" class="h-full w-full object-cover">
                                </button>
                            </template>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex aspect-square items-center justify-center rounded-3xl border border-zinc-100 bg-zinc-50 text-zinc-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                    </svg>
                </div>
            @endif
        </div>

        <div class="lg:py-4">
            <div class="flex flex-wrap items-center gap-2">
                @if ($product->brand)
                    <a href="{{ route('shop.brand', $product->brand->slug) }}"
                        class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-zinc-600 hover:bg-primary/10 hover:text-primary">
                        {{ $product->brand->name }}
                    </a>
                @endif

                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-500">
                    {{ $product->product_type === 'digital' ? __('Digital') : __('Physical') }}
                </span>

                @if ($product->is_upcoming)
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ __('Upcoming') }}</span>
                @endif
            </div>

            <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-zinc-900">{{ $product->name }}</h1>

            {{-- On variant products the base SKU belongs to the parent — the
                 picker above shows the selected combination's own SKU instead. --}}
            @if ($product->sku && ! $hasVariations)
                <p class="mt-1 text-sm text-zinc-500">{{ __('SKU: :sku', ['sku' => $product->sku]) }}</p>
            @endif

            @if (! $hasVariations)
                <div class="mt-6 flex items-baseline gap-3">
                    @if ($baseDiscountLabel)
                        <span class="text-3xl font-extrabold text-zinc-900">{{ $baseDiscountLabel }}</span>
                        <span class="text-lg text-zinc-400 line-through">{{ format_money($product->price) }}</span>
                    @else
                        <span class="text-3xl font-extrabold text-zinc-900">{{ format_money($product->price) }}</span>
                    @endif
                </div>

                @if (! $product->is_upcoming)
                    <p class="mt-2 text-sm {{ $product->inStock() ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $baseStockLabel }}
                    </p>
                @endif
            @endif

            {{-- The option picker lives inside the add-to-cart component below, so the
                 *picked* combination is what actually lands in the cart line. --}}

            <div class="mt-6 flex flex-wrap gap-2 text-sm text-zinc-600">
                @if ($product->warranty_months > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 px-3 py-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        {{ __(':months months warranty', ['months' => $product->warranty_months]) }}
                    </span>
                @endif

                <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 px-3 py-1.5">
                    @if ($product->charge_shipping)
                        <span>{{ __('Shipping charges apply') }}</span>
                    @else
                        <span class="font-medium text-emerald-600">{{ __('Free shipping') }}</span>
                    @endif
                </span>
            </div>

            <div class="mt-6">
                <livewire:frontend.add-to-cart-button
                    :product-id="$product->id"
                    :adjustable="true"
                    :quantity="1"
                    :show-picker="$hasVariations"
                    :key="'add-to-cart-'.$product->id"
                />
            </div>

            @if (filled($product->description))
                <div class="mt-8 border-t border-zinc-100 pt-6">
                    <h2 class="mb-3 text-lg font-bold text-zinc-900">{{ __('Description') }}</h2>
                    <div class="whitespace-pre-line leading-relaxed text-zinc-600">{{ $product->description }}</div>
                </div>
            @endif

            @if ($product->categories->isNotEmpty() || $product->tags->isNotEmpty())
                <div class="mt-8 border-t border-zinc-100 pt-6">
                    @if ($product->categories->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-zinc-500">{{ __('Categories:') }}</span>
                            @foreach ($product->categories as $category)
                                <a href="{{ route('shop.category', $category->slug) }}"
                                    class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-medium text-zinc-600 transition hover:border-primary hover:text-primary">
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($product->tags->isNotEmpty())
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="text-sm text-zinc-500">{{ __('Tags:') }}</span>
                            @foreach ($product->tags as $tag)
                                <a href="{{ route('shop.tag', $tag->slug) }}"
                                    class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 transition hover:bg-primary/10 hover:text-primary">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($product->faqs->where('is_active', true)->isNotEmpty())
        <section class="mx-auto mt-14 max-w-3xl">
            <h2 class="mb-5 text-2xl font-bold text-zinc-900">{{ __('Frequently asked questions') }}</h2>
            <div class="space-y-3">
                @foreach ($product->faqs->where('is_active', true) as $faq)
                    <details class="group rounded-2xl border border-zinc-100 bg-white shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-medium text-zinc-900">
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
            <h2 class="mb-6 text-2xl font-bold text-zinc-900">{{ $section->name }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($section->localizedCards() as $card)
                    <div class="group overflow-hidden rounded-2xl border border-zinc-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        @if ($card['image'])
                            <div class="relative aspect-square overflow-hidden bg-zinc-100">
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="p-4">
                            @if ($card['title'])
                                <h3 class="font-semibold text-zinc-900">{{ $card['title'] }}</h3>
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
            <h2 class="mb-6 text-2xl font-bold text-zinc-900">{{ __('You may also like') }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
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
</body>
</html>