<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

@php
    $crumbs = [
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('Flash Deals'), 'url' => null],
    ];
@endphp

<main>
    @include('theme-ecommerce::partials._breadcrumbs', ['crumbs' => $crumbs])

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="mb-6">
            <h1 class="text-center text-lg font-bold uppercase tracking-wide text-sf-text md:text-2xl">⚡ {{ __('Flash Deals') }}</h1>
            <p class="mt-1 text-center text-sm text-gray-600">{{ __('Limited-time prices — grab them before the clock runs out.') }}</p>
        </div>

        @forelse ($deals as $deal)
            <section class="mb-10">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-card bg-white px-4 py-3 shadow-sm">
                    <div>
                        <h2 class="text-base font-bold text-sf-text md:text-lg">{{ $deal->name }}</h2>
                        <p class="text-sm font-semibold text-sale">{{ $deal->valueLabel() }}</p>
                    </div>

                    {{-- Counts down to ends_at; the server's own clock decides the start
                         so a wrong device clock only skews the display, never the price. --}}
                    <div x-data="{
                            left: {{ max(0, (int) now()->diffInSeconds($deal->ends_at, false)) }},
                            init() { setInterval(() => { if (this.left > 0) this.left-- }, 1000) },
                            part(n) { return String(n).padStart(2, '0') },
                            get d() { return Math.floor(this.left / 86400) },
                            get h() { return Math.floor((this.left % 86400) / 3600) },
                            get m() { return Math.floor((this.left % 3600) / 60) },
                            get s() { return this.left % 60 },
                        }"
                        class="flex items-center gap-2 text-sm" role="timer" aria-label="{{ __('Time left') }}">
                        <span class="font-semibold text-zinc-500">{{ __('Ends in') }}</span>
                        <template x-if="d > 0">
                            <span class="rounded bg-zinc-900 px-2 py-1 font-mono font-bold text-white"><span x-text="d"></span>{{ __('d') }}</span>
                        </template>
                        <span class="rounded bg-zinc-900 px-2 py-1 font-mono font-bold text-white" x-text="part(h)"></span>
                        <span class="font-bold text-zinc-500">:</span>
                        <span class="rounded bg-zinc-900 px-2 py-1 font-mono font-bold text-white" x-text="part(m)"></span>
                        <span class="font-bold text-zinc-500">:</span>
                        <span class="rounded bg-sale px-2 py-1 font-mono font-bold text-white" x-text="part(s)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                    @foreach ($deal->products as $product)
                        @include('theme-ecommerce::partials._product-card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-card border border-dashed border-zinc-300 bg-white px-6 py-16 text-center">
                <h2 class="text-lg font-semibold text-sf-text">{{ __('No flash deals right now') }}</h2>
                <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">{{ __('Check back soon — new deals show up here the moment they start.') }}</p>
                <a href="{{ route('shop') }}"
                    class="mt-6 inline-block rounded-full bg-sf-button px-6 py-2.5 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
                    {{ __('Browse products') }}
                </a>
            </div>
        @endforelse
    </div>
</main>

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
