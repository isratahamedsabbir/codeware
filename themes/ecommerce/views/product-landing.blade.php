<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
    <style>
        @keyframes lp-float { 0%, 100% { transform: translateY(0) rotate(-1deg); } 50% { transform: translateY(-14px) rotate(1deg); } }
        @keyframes lp-marquee { to { transform: translateX(-50%); } }
        @keyframes lp-ring { 0% { box-shadow: 0 0 0 0 rgba(20, 72, 184, .55); } 70%, 100% { box-shadow: 0 0 0 16px rgba(20, 72, 184, 0); } }
        .lp-float { animation: lp-float 5s ease-in-out infinite; }
        .lp-marquee { animation: lp-marquee 28s linear infinite; }
        .lp-cta { animation: lp-ring 2s ease-out infinite; }
        @media (prefers-reduced-motion: reduce) { .lp-float, .lp-marquee, .lp-cta { animation: none; } }
    </style>
</head>
<body class="bg-white font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

{{-- Campaign landing page: shown instead of product.blade.php when the visit
     carries `?fbclid=` (see RendersProduct::product()). Same product data, a
     single-purpose layout built for a phone in a Facebook feed: one big
     image, the price, and the order block above the fold. Everything shown as
     proof (rating, reviews, sold count, stock) is read from real data. --}}
@php
    $galleryImages = collect()
        ->push($product->featured_image)
        ->merge($product->gallery->pluck('url'))
        ->filter()
        ->values();

    $hasVariations = collect($product->variations ?? [])->contains(fn (array $row) => $row['visible'] ?? true);
    $discount = $product->effectiveDiscount();
    $priceLabel = format_money($discount ?? $product->price);
    $oldPriceLabel = $discount !== null ? format_money($product->price) : null;
    $discountPercent = ($discount !== null && (float) $product->price > 0)
        ? (int) round(((float) $product->price - $discount) / (float) $product->price * 100)
        : null;

    $faqs = $product->faqs->where('is_active', true);
    $contactPhone = \App\Models\Setting::get('contact_phone');

    $reviewsOn = \App\Support\Features::enabled('reviews');
    $rating = $reviewsOn && $product->averageRating() !== null
        ? ['average' => $product->averageRating(), 'count' => $product->approvedReviews()->count()]
        : null;
    $reviews = $rating
        ? $product->approvedReviews()->with('user')->latest()->limit(3)->get()
        : collect();
    $sold = $product->soldQuantity();
    $lowStock = ! $product->is_upcoming && $product->inStock() && ! $hasVariations && (int) $product->quantity <= 10
        ? (int) $product->quantity
        : null;

    // Bulleted lines in the description become benefit cards; whatever text is
    // left over stays as the intro above them.
    $descriptionHtml = (string) $product->description;
    preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $descriptionHtml, $liMatches);
    $benefits = collect($liMatches[1])->map(fn ($text) => trim(strip_tags($text)))->filter()->values();
    $asCards = $benefits->count() >= 3;
    $intro = $asCards ? preg_replace('/<(ul|ol)\b.*?<\/\1>/is', '', $descriptionHtml) : $descriptionHtml;
    $hasIntro = trim(strip_tags((string) $intro)) !== '';

    // "Label: value" bullets in the specifications become table rows; anything
    // that doesn't parse that way falls back to the plain rich text.
    preg_match_all('/<li[^>]*>(.*?)<\/li>/is', (string) $product->specifications, $specMatches);
    $specRows = collect($specMatches[1])
        ->map(function ($html) {
            $text = trim(html_entity_decode(strip_tags($html)));

            return str_contains($text, ':') ? array_map('trim', explode(':', $text, 2)) : null;
        })
        ->filter(fn ($row) => $row && $row[0] !== '' && $row[1] !== '')
        ->values();
    $specAsTable = $specRows->count() >= 2;

    $trust = collect([
        [__('Cash on delivery'), __('Pay when it reaches you'), 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z'],
        [__('Genuine product'), __('Quality you can trust'), 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        [__('Fast delivery'), __('Safe and quick, nationwide'), 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12'],
    ]);

    if (! $product->charge_shipping) {
        $trust->push([__('Free shipping'), __('No delivery charge'), 'M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z']);
    }

    if ($product->warranty_months > 0) {
        $trust->push([__(':months months warranty', ['months' => $product->warranty_months]), __('We stand behind it'), 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z']);
    }

    $ctaSoft = 'lp-cta inline-flex items-center justify-center gap-2 rounded-full bg-[#1448b8] px-8 py-3.5 text-base font-extrabold text-white shadow-lg shadow-[#1448b8]/30 transition hover:scale-105 hover:bg-[#0f3a98]';
    $cartIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>';
    $starPath = 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z';
@endphp

<main x-data="{ orderVisible: false }" class="overflow-x-clip">
    {{-- Ticker: the page's selling points, scrolling, above the hero. --}}
    <div class="overflow-hidden bg-[#d7ff1f] py-2 text-sm font-bold text-[#0b2a73]" aria-hidden="true">
        <div class="lp-marquee flex w-max gap-10 whitespace-nowrap">
            @foreach (range(1, 2) as $copy)
                @foreach ($trust as [$heading])
                    <span class="inline-flex items-center gap-10">{{ $heading }} <span>★</span></span>
                @endforeach
                @if ($discountPercent)
                    <span class="inline-flex items-center gap-10">{{ __('Save :percent%', ['percent' => $discountPercent]) }} <span>★</span></span>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Hero: on a phone it reads headline → product → order block; from md up
         the order block tucks under the headline beside a tall product image. --}}
    <section class="relative overflow-hidden bg-linear-to-b from-[#1448b8] to-[#0d3585] px-4 pb-24 pt-8 text-white sm:px-6 md:pb-28 md:pt-14">
        <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-[#d7ff1f]/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-sky-400/20 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-6xl gap-x-12 gap-y-8 md:grid-cols-2 md:items-center">
            <div class="text-center md:col-start-1 md:row-start-1 md:text-left">
                @if ($rating || $sold > 0)
                    <div class="mb-4 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm md:justify-start">
                        @if ($rating)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 backdrop-blur">
                                <span class="flex">
                                    @foreach (range(1, 5) as $i)
                                        <svg viewBox="0 0 24 24" class="h-4 w-4 {{ $i <= round($rating['average']) ? 'fill-[#d7ff1f] text-[#d7ff1f]' : 'fill-white/30 text-white/30' }}" stroke="currentColor" stroke-width="1"><path stroke-linejoin="round" d="{{ $starPath }}" /></svg>
                                    @endforeach
                                </span>
                                <strong>{{ number_format($rating['average'], 1) }}</strong>
                                <span class="text-white/70">({{ $rating['count'] }})</span>
                            </span>
                        @endif
                        @if ($sold > 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 backdrop-blur">
                                🔥 {{ __(':count sold', ['count' => number_format($sold)]) }}
                            </span>
                        @endif
                    </div>
                @endif

                <h1 class="text-4xl font-extrabold leading-[1.1] text-[#d7ff1f] sm:text-5xl lg:text-6xl">{{ $product->name }}</h1>

                @if (filled($product->excerpt))
                    <div class="rich-text mt-4 text-base leading-relaxed text-white/85 sm:text-lg">@richtext($product->excerpt)</div>
                @endif

                <div class="mt-6 flex flex-wrap items-end justify-center gap-x-4 gap-y-1 md:justify-start">
                    <span class="text-5xl font-extrabold leading-none sm:text-6xl">{{ $priceLabel }}</span>
                    @if ($oldPriceLabel)
                        <span class="pb-1 text-xl text-white/55 line-through">{{ $oldPriceLabel }}</span>
                    @endif
                    @if ($discountPercent)
                        <span class="mb-1 -rotate-3 rounded-lg bg-[#d7ff1f] px-3 py-1 text-sm font-extrabold text-[#0b2a73] shadow-lg">{{ __('Save :percent%', ['percent' => $discountPercent]) }}</span>
                    @endif
                </div>
            </div>

            <div x-data="{ active: 0, images: @js($galleryImages->all()) }" class="mx-auto w-full max-w-sm md:col-start-2 md:row-span-2 md:row-start-1 md:max-w-md">
                <div class="relative">
                    <div class="absolute inset-4 rounded-full bg-[#d7ff1f]/25 blur-3xl"></div>

                    <div class="lp-float relative aspect-square overflow-hidden rounded-[2rem] bg-white/10 shadow-2xl shadow-black/40 ring-4 ring-white/30">
                        @if ($galleryImages->isNotEmpty())
                            <template x-for="(image, index) in images" :key="index">
                                <img :src="image" alt="{{ $product->name }}" x-show="active === index"
                                    x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0"
                                    class="absolute inset-0 h-full w-full object-cover">
                            </template>
                        @else
                            <div class="flex h-full w-full items-center justify-center text-white/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    @if ($discountPercent)
                        <div class="absolute -right-2 -top-3 flex h-20 w-20 rotate-12 flex-col items-center justify-center rounded-full bg-[#d7ff1f] text-[#0b2a73] shadow-xl ring-4 ring-white sm:-right-4 sm:h-24 sm:w-24">
                            <span class="text-2xl font-extrabold leading-none sm:text-3xl">{{ $discountPercent }}%</span>
                            <span class="text-[11px] font-bold uppercase tracking-wide">{{ __('Off') }}</span>
                        </div>
                    @endif
                </div>

                @if ($galleryImages->count() > 1)
                    <div class="mt-5 flex justify-center gap-3">
                        <template x-for="(image, index) in images" :key="index">
                            <button type="button" @click="active = index" :aria-label="`{{ __('Image') }} ${index + 1}`"
                                :class="active === index ? 'scale-110 ring-[#d7ff1f] opacity-100' : 'ring-white/30 opacity-70'"
                                class="h-14 w-14 overflow-hidden rounded-xl ring-2 transition">
                                <img :src="image" alt="" loading="lazy" class="h-full w-full object-cover">
                            </button>
                        </template>
                    </div>
                @endif
            </div>

            {{-- Order block: the regular add-to-cart component (variant picker
                 included), then on to the normal checkout. Every other button on
                 the page scrolls back up to it. --}}
            <div id="order" x-intersect:enter="orderVisible = true" x-intersect:leave="orderVisible = false"
                class="mx-auto w-full max-w-md scroll-mt-4 rounded-3xl bg-white p-5 text-left text-zinc-800 shadow-2xl shadow-black/30 ring-4 ring-[#d7ff1f]/60 md:col-start-1 md:row-start-2 md:mx-0">
                @if ($lowStock)
                    <p class="mb-3 flex items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2 text-sm font-bold text-red-600">
                        <span class="relative flex size-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400"></span><span class="relative inline-flex size-2.5 rounded-full bg-red-500"></span></span>
                        {{ __('Only :count left in stock', ['count' => $lowStock]) }}
                    </p>
                @endif

                <livewire:frontend.add-to-cart-button
                    :product-id="$product->id"
                    :adjustable="true"
                    :quantity="1"
                    :show-picker="$hasVariations"
                    :key="'landing-add-to-cart-'.$product->id"
                />

                <a href="{{ route('checkout') }}" class="{{ $ctaSoft }} mt-3 w-full">{{ __('Go to checkout') }}</a>
            </div>
        </div>
    </section>

    {{-- Trust tiles overlapping the hero's lower edge. --}}
    <section class="relative z-10 -mt-14 px-4 sm:px-6">
        <div class="mx-auto flex max-w-5xl flex-wrap justify-center gap-3 sm:gap-4">
            @foreach ($trust as [$heading, $text, $icon])
                <div class="flex w-[calc(50%-0.375rem)] items-center gap-3 rounded-2xl bg-white p-3.5 shadow-xl shadow-[#1448b8]/15 ring-1 ring-zinc-100 sm:w-[calc(25%-0.75rem)] sm:flex-col sm:p-5 sm:text-center">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#1448b8] text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-extrabold leading-tight text-[#0b2a73]">{{ $heading }}</span>
                        <span class="mt-0.5 block text-xs leading-snug text-zinc-500">{{ $text }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Why this product --}}
    @if (filled($product->description))
        <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6">
            <h2 class="text-center text-2xl font-extrabold text-[#0b2a73] sm:text-4xl">{{ __('Why is :name special?', ['name' => $product->name]) }}</h2>
            <div class="mx-auto mt-3 h-1.5 w-20 rounded-full bg-[#d7ff1f]"></div>

            @if ($hasIntro)
                <div class="rich-text mx-auto mt-8 max-w-3xl text-center text-base leading-relaxed text-zinc-600 sm:text-lg">@richtext($intro)</div>
            @endif

            @if ($asCards)
                <div class="mt-10 grid gap-4 sm:grid-cols-2">
                    @foreach ($benefits as $benefit)
                        <div class="group flex items-start gap-4 rounded-2xl bg-linear-to-br from-[#1448b8]/[0.07] to-[#d7ff1f]/20 p-5 ring-1 ring-[#1448b8]/10 transition hover:-translate-y-1 hover:shadow-lg">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#1448b8] text-lg font-bold text-white transition group-hover:scale-110">✓</span>
                            <p class="pt-1.5 font-semibold leading-snug text-[#0b2a73]">{{ $benefit }}</p>
                        </div>
                    @endforeach
                </div>
            @elseif (! $hasIntro)
                <div class="rich-text mt-10 text-base leading-relaxed text-zinc-700">@richtext($product->description)</div>
            @endif

            <div class="mt-10 text-center">
                <a href="#order" class="{{ $ctaSoft }}">{!! $cartIcon !!} {{ __('Order now') }}</a>
            </div>
        </section>
    @endif

    {{-- Real, approved reviews --}}
    @if ($reviews->isNotEmpty())
        <section class="bg-zinc-50 px-4 py-16 sm:px-6">
            <div class="mx-auto max-w-5xl">
                <h2 class="text-center text-2xl font-extrabold text-[#0b2a73] sm:text-4xl">{{ __('What customers say') }}</h2>
                <div class="mx-auto mt-3 h-1.5 w-20 rounded-full bg-[#d7ff1f]"></div>

                <div class="mt-10 grid gap-5 md:grid-cols-3">
                    @foreach ($reviews as $review)
                        <figure class="flex flex-col rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-100">
                            <span class="flex">
                                @foreach (range(1, 5) as $i)
                                    <svg viewBox="0 0 24 24" class="h-5 w-5 {{ $i <= $review->rating ? 'fill-amber-400 text-amber-400' : 'fill-zinc-200 text-zinc-200' }}" stroke="currentColor" stroke-width="1"><path stroke-linejoin="round" d="{{ $starPath }}" /></svg>
                                @endforeach
                            </span>
                            @if ($review->title)
                                <p class="mt-3 font-bold text-[#0b2a73]">{{ $review->title }}</p>
                            @endif
                            <blockquote class="mt-2 flex-1 leading-relaxed text-zinc-600">{{ $review->body }}</blockquote>
                            <figcaption class="mt-4 text-sm font-semibold text-zinc-500">— {{ $review->user?->name ?? __('Customer') }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Specifications --}}
    @if (filled($product->specifications))
        <section class="px-4 pb-16 pt-6 sm:px-6">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-2xl font-extrabold text-[#0b2a73] sm:text-4xl">{{ __('Specifications') }}</h2>
                <div class="mx-auto mt-3 h-1.5 w-20 rounded-full bg-[#d7ff1f]"></div>

                @if ($specAsTable)
                    <dl class="mt-10 overflow-hidden rounded-2xl text-left shadow-lg shadow-[#1448b8]/10 ring-1 ring-zinc-200">
                        @foreach ($specRows as [$label, $value])
                            <div class="grid grid-cols-5 gap-4 px-5 py-4 sm:px-7 {{ $loop->even ? 'bg-zinc-50' : 'bg-white' }} {{ $loop->last ? '' : 'border-b border-zinc-100' }}">
                                <dt class="col-span-2 font-bold text-[#0b2a73]">{{ $label }}</dt>
                                <dd class="col-span-3 text-zinc-700">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <div class="rich-text mt-10 rounded-2xl bg-white p-6 text-left leading-relaxed text-zinc-700 shadow-lg shadow-[#1448b8]/10 ring-1 ring-zinc-200 sm:p-8 [&_li]:my-2 [&_ul]:list-disc [&_ul]:pl-5">@richtext($product->specifications)</div>
                @endif
            </div>
        </section>
    @endif

    {{-- FAQ --}}
    @if ($faqs->isNotEmpty())
        <section class="bg-zinc-50 px-4 py-16 sm:px-6">
            <div class="mx-auto max-w-4xl">
                <h2 class="text-center text-2xl font-extrabold text-[#0b2a73] sm:text-4xl">{{ __('Questions we get asked often') }}</h2>
                <div class="mx-auto mt-3 h-1.5 w-20 rounded-full bg-[#d7ff1f]"></div>

                <div class="mt-10 space-y-3">
                    @foreach ($faqs as $faq)
                        <details class="group rounded-2xl bg-white shadow-sm ring-1 ring-zinc-100 open:ring-[#1448b8]/30" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-bold text-[#0b2a73]">
                                {{ $faq->question }}
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#1448b8]/10 text-[#1448b8] transition group-open:rotate-180 group-open:bg-[#1448b8] group-open:text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="px-5 pb-5 leading-relaxed text-zinc-600">{{ $faq->answer }}</div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Call us --}}
    @if ($contactPhone)
        <section class="bg-zinc-50 px-4 py-14 sm:px-6">
            <div class="mx-auto max-w-3xl rounded-3xl bg-linear-to-br from-[#1448b8] to-[#0d3585] px-6 py-10 text-center text-white shadow-xl shadow-[#1448b8]/25">
                <p class="text-2xl font-bold">{{ __('Call us if you need help') }}</p>
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $contactPhone) }}"
                    class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#d7ff1f] px-7 py-3 font-extrabold text-[#0b2a73] shadow-lg transition hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>
                    {{ $contactPhone }}
                </a>
            </div>
        </section>
    @endif

    {{-- Mobile sticky bar: the order button stays in reach until the order
         block in the hero is on screen. --}}
    <div x-show="! orderVisible" x-cloak x-transition
        class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-3 border-t border-zinc-200 bg-white/95 py-3 pl-4 pr-20 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] backdrop-blur md:hidden">
        <div class="min-w-0">
            <p class="truncate text-xs text-zinc-500">{{ $product->name }}</p>
            <p class="text-lg font-extrabold text-[#1448b8]">{{ $priceLabel }}</p>
        </div>
        <a href="#order" class="{{ $ctaSoft }} px-6! py-2.5!">{!! $cartIcon !!} {{ __('Order now') }}</a>
    </div>
</main>

{{-- The footer sits flush under the last section (no white gap), and on a phone
     the wrapper leaves room so the sticky order bar never covers its last line. --}}
<div class="bg-sf-footer pb-20 md:pb-0">
    @include('theme-ecommerce::partials._footer', ['withoutPopup' => true, 'flushTop' => true])
</div>

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
