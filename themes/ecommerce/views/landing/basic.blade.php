<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
    <style>
        @keyframes lp-shine { 0% { transform: translateX(-120%) skewX(-20deg); } 60%, 100% { transform: translateX(260%) skewX(-20deg); } }
        .lp-shine { position: relative; overflow: hidden; }
        .lp-shine::after { content: ''; position: absolute; inset: 0 auto 0 0; width: 40%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .6), transparent); animation: lp-shine 3.2s ease-in-out infinite; pointer-events: none; }
        @media (prefers-reduced-motion: reduce) { .lp-shine::after { animation: none; } }
    </style>
</head>
<body class="bg-white font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

{{-- Basic landing page: product name, one big photo, an order button and the
     order form itself right on the page (no cart, no checkout step), then the
     description folded away. Shown instead of product.blade.php when the visit
     carries `?fbclid=` (see RendersProduct::product()). --}}
@php
    $galleryImages = collect()
        ->push($product->featured_image)
        ->merge($product->gallery->pluck('url'))
        ->filter()
        ->values();

    $contactPhone = \App\Models\Setting::get('contact_phone');
    $hasDetails = filled($product->description) || filled($product->specifications);

    // "Label: value" bullets in the specifications become table rows; anything
    // that doesn't parse that way stays as the plain rich text.
    preg_match_all('/<li[^>]*>(.*?)<\/li>/is', (string) $product->specifications, $specMatches);
    $specRows = collect($specMatches[1])
        ->map(function ($html) {
            $text = trim(html_entity_decode(strip_tags($html)));

            return str_contains($text, ':') ? array_map('trim', explode(':', $text, 2)) : null;
        })
        ->filter(fn ($row) => $row && $row[0] !== '' && $row[1] !== '')
        ->values();
    $specAsTable = $specRows->count() >= 2;
@endphp

<main class="overflow-x-clip">
    {{-- Product name --}}
    <div class="border-b border-zinc-200 bg-zinc-100 px-4 py-3 text-center">
        <h1 class="text-lg font-bold text-sf-heading sm:text-xl">{{ $product->name }}</h1>
    </div>

    {{-- Photo(s) on one side, the product details on the other (stacked on a
         phone). The photo stays in view while the details scroll past it, and
         auto-advances when there is more than one. --}}
    <section class="mx-auto grid max-w-5xl items-start gap-6 px-4 pb-8 pt-6 {{ $hasDetails ? 'md:grid-cols-2 md:gap-10' : '' }}">
        <div class="{{ $hasDetails ? 'md:sticky md:top-28' : 'mx-auto w-full max-w-md' }}"
            x-data="{ active: 0, images: @js($galleryImages->all()), timer: null,
                start() { if (this.images.length > 1) this.timer = setInterval(() => this.active = (this.active + 1) % this.images.length, 4000) },
                go(i) { this.active = i; clearInterval(this.timer); this.start() } }"
            x-init="start()">
            <div class="relative aspect-square w-full overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-zinc-200">
                @forelse ($galleryImages as $image)
                    <img src="{{ $image }}" alt="{{ $product->name }}" @if (! $loop->first) loading="lazy" @endif
                        x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                        x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0"
                        class="absolute inset-0 h-full w-full object-contain p-3">
                @empty
                    <div class="flex h-full w-full items-center justify-center bg-zinc-50 text-zinc-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" /></svg>
                    </div>
                @endforelse
            </div>

            @if ($galleryImages->count() > 1)
                <div class="mt-4 flex justify-center gap-2">
                    @foreach ($galleryImages as $image)
                        <button type="button" @click="go({{ $loop->index }})" aria-label="{{ __('Image') }} {{ $loop->iteration }}"
                            :class="active === {{ $loop->index }} ? 'w-6 bg-amber-400' : 'w-2.5 bg-zinc-300'"
                            class="h-2.5 rounded-full transition-all"></button>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($hasDetails)
            {{-- Two tabs (one when the product has only one of them). --}}
            @php
                $hasDescription = filled($product->description);
                $hasSpecs = filled($product->specifications);
                $firstTab = $hasDescription ? 'details' : 'specs';
            @endphp
            <div x-data="{ tab: '{{ $firstTab }}' }" class="overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-zinc-200">
                <div class="flex border-b border-zinc-200 bg-zinc-50" role="tablist">
                    @if ($hasDescription)
                        <button type="button" role="tab" @click="tab = 'details'" :aria-selected="tab === 'details'"
                            :class="tab === 'details' ? 'border-brand bg-white text-brand' : 'border-transparent text-zinc-500 hover:text-sf-heading'"
                            class="-mb-px flex-1 border-b-2 px-4 py-3.5 text-sm font-bold transition sm:text-base">{{ __('Product details') }}</button>
                    @endif
                    @if ($hasSpecs)
                        <button type="button" role="tab" @click="tab = 'specs'" :aria-selected="tab === 'specs'"
                            :class="tab === 'specs' ? 'border-brand bg-white text-brand' : 'border-transparent text-zinc-500 hover:text-sf-heading'"
                            class="-mb-px flex-1 border-b-2 px-4 py-3.5 text-sm font-bold transition sm:text-base">{{ __('Specifications') }}</button>
                    @endif
                </div>

                @if ($hasDescription)
                    <div x-show="tab === 'details'" role="tabpanel" class="p-5 sm:p-6">
                        <div class="rich-text leading-relaxed text-zinc-600 [&_h2]:mb-2 [&_h2]:mt-5 [&_h2]:text-base [&_h2]:font-bold [&_h2]:text-sf-heading [&_h3]:mb-2 [&_h3]:mt-5 [&_h3]:text-base [&_h3]:font-bold [&_h3]:text-sf-heading [&_li]:my-1.5 [&_p]:mb-3 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:marker:text-brand">@richtext($product->description)</div>
                    </div>
                @endif

                @if ($hasSpecs)
                    <div x-show="tab === 'specs'" @if ($firstTab !== 'specs') x-cloak @endif role="tabpanel">
                        @if ($specAsTable)
                            <dl>
                                @foreach ($specRows as [$label, $value])
                                    <div class="grid grid-cols-5 gap-3 px-5 py-3 text-sm sm:px-6 {{ $loop->odd ? 'bg-zinc-50' : 'bg-white' }}">
                                        <dt class="col-span-2 font-semibold text-sf-heading">{{ $label }}</dt>
                                        <dd class="col-span-3 text-zinc-600">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <div class="rich-text p-5 leading-relaxed text-zinc-600 sm:p-6 [&_li]:my-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:marker:text-brand">@richtext($product->specifications)</div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </section>

    {{-- Big order button: scrolls to the form below. --}}
    <div class="px-4 pb-3">
        <a href="#order" class="lp-shine mx-auto block max-w-3xl rounded bg-linear-to-b from-amber-300 to-amber-500 py-3.5 text-center text-2xl font-extrabold text-zinc-900 shadow-md transition hover:brightness-105 sm:py-4 sm:text-3xl">{{ __('Order now') }}</a>
    </div>

    @if ($contactPhone)
        <a href="tel:{{ preg_replace('/[^\d+]/', '', $contactPhone) }}"
            class="group mt-3 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-linear-to-r from-sf-footer via-brand to-sf-footer px-4 py-3 text-white shadow-inner">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/30 transition group-hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
            </span>
            <span class="text-sm font-semibold text-white/90 sm:text-base">{{ __('Call for information') }}</span>
            <span class="rounded-full bg-white px-4 py-1 text-base font-extrabold tracking-wide text-sf-footer shadow transition group-hover:bg-amber-300 sm:text-lg">{{ $contactPhone }}</span>
        </a>
    @endif

    {{-- Order form --}}
    <section class="bg-zinc-50 px-3 py-4 sm:px-4 sm:py-5">
        <livewire:frontend.landing-order :product-id="$product->id" :key="'landing-order-'.$product->id" />
    </section>

    <div class="bg-zinc-50 pb-6"></div>
</main>

<div class="bg-sf-footer">
    @include('theme-ecommerce::partials._footer', ['withoutPopup' => true, 'flushTop' => true])
</div>

@include('theme-ecommerce::partials._landing-call')
@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
