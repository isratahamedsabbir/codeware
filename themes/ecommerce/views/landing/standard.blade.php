<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
    <style>
        @keyframes lp-ring { 0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, .55); } 70%, 100% { box-shadow: 0 0 0 14px rgba(255, 255, 255, 0); } }
        @keyframes lp-shine { 0% { transform: translateX(-120%) skewX(-20deg); } 60%, 100% { transform: translateX(260%) skewX(-20deg); } }
        .lp-cta { animation: lp-ring 2s ease-out infinite; }
        .lp-shine { position: relative; overflow: hidden; }
        .lp-shine::after { content: ''; position: absolute; inset: 0 auto 0 0; width: 40%; background: linear-gradient(90deg, transparent, rgba(120, 140, 255, .35), transparent); animation: lp-shine 3.2s ease-in-out infinite; pointer-events: none; }
        @media (prefers-reduced-motion: reduce) { .lp-cta, .lp-shine::after { animation: none; } }
    </style>
</head>
<body class="bg-white font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

{{-- Standard landing page: a blue sales page — hero with the photos, why it is
     special, the offer, why buy from us, FAQ, a call box, and the order form
     at the bottom. Shown instead of product.blade.php when the visit carries
     `?fbclid=` (see RendersProduct::product()). Everything shown comes from the
     product: nothing is invented. --}}
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

    $faqs = $product->faqs->where('is_active', true);
    $contactPhone = \App\Models\Setting::get('contact_phone');

    // Bulleted lines in the description become the "why special" list; whatever
    // text is left over stays as the intro above it.
    $descriptionHtml = (string) $product->description;
    preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $descriptionHtml, $liMatches);
    $benefits = collect($liMatches[1])->map(fn ($text) => trim(strip_tags($text)))->filter()->values();
    $asList = $benefits->count() >= 2;
    $intro = $asList ? preg_replace('/<(ul|ol)\b.*?<\/\1>/is', '', $descriptionHtml) : $descriptionHtml;
    $hasIntro = trim(strip_tags((string) $intro)) !== '';

    $why = collect([
        [__('Cash on delivery'), __('Pay when the product reaches your hands.')],
        [__('Genuine product'), __('Carefully selected and packed with quality in mind.')],
        [__('Fast delivery'), __('Safe and quick delivery all over the country.')],
    ]);

    if (! $product->charge_shipping) {
        $why->push([__('Free shipping'), __('No delivery charge on this product.')]);
    }

    if ($product->warranty_months > 0) {
        $why->push([__(':months months warranty', ['months' => $product->warranty_months]), __('We stand behind what we sell.')]);
    }

    $cartIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>';
    $checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';

    // Order button on white sections (blue) and on blue sections (white).
    $ctaBlue = 'lp-shine inline-flex items-center justify-center gap-2 rounded-full bg-[#1448b8] px-8 py-3 text-base font-extrabold text-white shadow-lg shadow-[#1448b8]/30 transition hover:scale-105 hover:bg-[#0f3a98]';
    $ctaWhite = 'lp-cta inline-flex items-center justify-center gap-2 rounded-full bg-white px-8 py-3 text-base font-extrabold text-[#1448b8] shadow-lg transition hover:scale-105';
    $heading = 'inline-block rounded bg-[#1448b8] px-4 py-2 text-lg font-bold text-white sm:text-xl';
@endphp

<main class="overflow-x-clip">
    {{-- Hero --}}
    <section class="rounded-b-[2.5rem] bg-[#1448b8] px-4 pb-10 pt-8 text-center text-white sm:px-6 sm:pt-10 sm:rounded-b-[4rem]">
        <div class="mx-auto max-w-4xl">
            <h1 class="text-3xl font-extrabold leading-tight text-[#d7ff1f] sm:text-5xl">{{ $product->name }}</h1>
            @if (filled($product->excerpt))
                <div class="rich-text mt-4 text-sm leading-relaxed text-white/85 sm:text-base">@richtext($product->excerpt)</div>
            @endif
        </div>

        @if ($galleryImages->isNotEmpty())
            <div class="mx-auto mt-8 flex max-w-5xl snap-x snap-mandatory gap-3 overflow-x-auto pb-2 sm:justify-center sm:gap-4">
                @foreach ($galleryImages->take(3) as $image)
                    <img src="{{ $image }}" alt="{{ $product->name }}" @if (! $loop->first) loading="lazy" @endif
                        class="aspect-square w-[72%] shrink-0 snap-center rounded-xl object-cover shadow-xl ring-1 ring-white/20 sm:w-1/3">
                @endforeach
            </div>
        @endif

        <a href="#order" class="{{ $ctaWhite }} mt-8">{!! $cartIcon !!} {{ __('I want to order') }}</a>
    </section>

    {{-- Why it is special --}}
    @if (filled($product->description))
        <section class="mx-auto max-w-5xl px-4 py-14 sm:px-6">
            <h2 class="{{ $heading }}">{{ __('Why is :name special?', ['name' => $product->name]) }}</h2>

            <div class="mt-8 grid items-center gap-8 md:grid-cols-2">
                <div>
                    @if ($hasIntro)
                        <div class="rich-text mb-5 leading-relaxed text-zinc-700">@richtext($intro)</div>
                    @endif
                    @if ($asList)
                        <ul class="space-y-3">
                            @foreach ($benefits as $benefit)
                                <li class="flex items-start gap-3 text-zinc-700">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#1448b8] text-[11px] font-bold text-white">✓</span>
                                    <span class="leading-relaxed">{{ $benefit }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @if ($galleryImages->isNotEmpty())
                    <img src="{{ $galleryImages->get(1) ?? $galleryImages->first() }}" alt="{{ $product->name }}" loading="lazy" class="mx-auto max-h-96 w-full max-w-sm rounded-2xl object-contain">
                @endif
            </div>
        </section>
    @endif

    {{-- The offer --}}
    <section class="bg-[#1448b8] px-4 py-12 text-center text-white sm:px-6">
        <div class="mx-auto max-w-4xl rounded border border-white/40 px-5 py-8 sm:px-10">
            <h2 class="text-2xl font-extrabold sm:text-4xl">{{ $product->name }}</h2>
            <p class="mt-3 flex flex-wrap items-baseline justify-center gap-x-3 text-3xl font-extrabold text-[#d7ff1f] sm:text-5xl">
                {{ __('Get it for just :price', ['price' => $priceLabel]) }}
                @if ($oldPriceLabel)
                    <span class="text-lg font-semibold text-white/55 line-through sm:text-2xl">{{ $oldPriceLabel }}</span>
                @endif
            </p>
            <p class="mt-3 text-xl font-bold sm:text-3xl">{{ __('Full cash on delivery') }}</p>
            @if ($oldPriceLabel)
                <p class="mt-4 text-sm font-semibold sm:text-base">{{ __('This offer is for a limited time — order today, do not wait.') }}</p>
            @endif
            <a href="#order" class="{{ $ctaWhite }} mt-6">{!! $cartIcon !!} {{ __('I want to order') }}</a>
        </div>
    </section>

    {{-- Why buy from us --}}
    <section class="px-4 py-14 sm:px-6">
        <div class="mx-auto max-w-5xl">
            <h2 class="text-center text-2xl font-extrabold text-[#1448b8] sm:text-3xl">{{ __('Why buy from us?') }}</h2>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($why as [$title, $text])
                    <div class="rounded-2xl bg-zinc-50 p-6 shadow-sm ring-1 ring-zinc-100">
                        <span class="text-[#1448b8]">{!! $checkIcon !!}</span>
                        <p class="mt-3 text-lg font-extrabold text-[#0b2a73]">{{ $title }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-zinc-500">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-8 text-center">
                <a href="#order" class="{{ $ctaBlue }}">{!! $cartIcon !!} {{ __('Order now') }}</a>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    @if ($faqs->isNotEmpty())
        <section class="mx-auto max-w-5xl px-4 pb-14 sm:px-6">
            <h2 class="block rounded bg-[#1448b8] px-4 py-3 text-center text-lg font-bold text-white sm:text-2xl">{{ __('Questions we get asked often') }}</h2>

            <div class="mt-4 divide-y divide-zinc-100 rounded-lg bg-white shadow-sm ring-1 ring-zinc-200">
                @foreach ($faqs as $faq)
                    <details class="group" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-4 font-semibold text-[#0b2a73]">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#1448b8]/10 text-sm leading-none text-[#1448b8] group-open:bg-[#1448b8] group-open:text-white"><span class="group-open:hidden">+</span><span class="hidden group-open:inline">−</span></span>
                            <span class="flex-1">{{ $faq->question }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-zinc-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
                        </summary>
                        <div class="px-4 pb-4 pl-12 leading-relaxed text-zinc-600">{{ $faq->answer }}</div>
                    </details>
                @endforeach
            </div>

            <div class="mt-8 text-center">
                <a href="#order" class="{{ $ctaBlue }}">{!! $cartIcon !!} {{ __('I want to order') }}</a>
            </div>
        </section>
    @endif

    {{-- Call us --}}
    @if ($contactPhone)
        <section class="bg-[#1448b8] px-4 py-10 text-center text-white sm:px-6">
            <div class="mx-auto max-w-3xl rounded border border-white/40 px-5 py-8">
                <p class="text-2xl font-bold sm:text-3xl">{{ __('Call us if you need help') }}</p>
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $contactPhone) }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-white px-6 py-2.5 font-bold text-[#1448b8] shadow-lg transition hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                    {{ $contactPhone }}
                </a>
            </div>
        </section>
    @endif

    {{-- Order form --}}
    <section class="bg-zinc-50 px-3 py-10 sm:px-4">
        <livewire:frontend.landing-order :product-id="$product->id" layout="wide" :key="'landing-order-'.$product->id" />
    </section>
</main>

<div class="bg-sf-footer">
    @include('theme-ecommerce::partials._footer', ['withoutPopup' => true, 'flushTop' => true])
</div>

@include('theme-ecommerce::partials._landing-call')
@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
