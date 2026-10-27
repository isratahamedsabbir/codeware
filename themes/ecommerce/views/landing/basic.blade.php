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
    $hasDetails =filled($product->description) || filled($product->specifications);
@endphp

<main class="overflow-x-clip">
    {{-- Product name --}}
    <div class="border-b border-zinc-200 bg-zinc-100 px-4 py-3 text-center">
        <h1 class="text-lg font-bold text-sf-heading sm:text-xl">{{ $product->name }}</h1>
    </div>

    {{-- Photo(s): auto-advances when there is more than one. --}}
    <section class="px-4 pb-6 pt-8"
        x-data="{ active: 0, images: @js($galleryImages->all()), timer: null,
            start() { if (this.images.length > 1) this.timer = setInterval(() => this.active = (this.active + 1) % this.images.length, 4000) },
            go(i) { this.active = i; clearInterval(this.timer); this.start() } }"
        x-init="start()">
        <div class="relative mx-auto aspect-square w-full max-w-xs sm:max-w-sm">
            @forelse ($galleryImages as $image)
                <img src="{{ $image }}" alt="{{ $product->name }}" @if (! $loop->first) loading="lazy" @endif
                    x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                    x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0"
                    class="absolute inset-0 h-full w-full object-contain">
            @empty
                <div class="flex h-full w-full items-center justify-center rounded-2xl bg-zinc-100 text-zinc-300">
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

    {{-- Description, folded away. --}}
    @if ($hasDetails)
        <section class="bg-zinc-50 px-3 pb-8 sm:px-4">
            <details class="group mx-auto max-w-5xl rounded border border-brand/25 bg-brand/5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-4 font-semibold text-sf-heading">
                    {{ __('Click to see product details') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
                </summary>
                <div class="space-y-6 border-t border-brand/15 bg-white px-4 py-5">
                    @if (filled($product->description))
                        <div class="rich-text leading-relaxed text-zinc-700">@richtext($product->description)</div>
                    @endif
                    @if (filled($product->specifications))
                        <div>
                            <h2 class="mb-2 font-bold text-sf-heading">{{ __('Specifications') }}</h2>
                            <div class="rich-text leading-relaxed text-zinc-700 [&_li]:my-1 [&_ul]:list-disc [&_ul]:pl-5">@richtext($product->specifications)</div>
                        </div>
                    @endif
                </div>
            </details>
        </section>
    @endif
</main>

<div class="bg-sf-footer">
    @include('theme-ecommerce::partials._footer', ['withoutPopup' => true, 'flushTop' => true])
</div>

@include('theme-ecommerce::partials._landing-call')
@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
