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
    $crumbs = [
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('My favorites'), 'url' => null],
    ];
@endphp

<main>
    @include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="mb-6">
            <h1 class="text-center text-lg font-bold uppercase tracking-wide text-sf-text md:text-2xl">{{ __('My favorites') }}</h1>
            <p class="mt-1 text-center text-sm text-gray-600">{{ __('Products you saved, ready when you are.') }}</p>
        </div>

        @if ($products->isEmpty())
            <div class="rounded-card border border-dashed border-zinc-300 bg-white px-6 py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                <h2 class="mt-4 text-lg font-semibold text-sf-text">{{ __('No favorites yet') }}</h2>
                <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                    {{ __('Tap the heart on any product to save it here — it stays even before you sign in.') }}
                </p>
                <a href="{{ route('shop') }}"
                    class="mt-6 inline-block rounded-full bg-sf-button px-6 py-2.5 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
                    {{ __('Browse products') }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                @foreach ($products as $product)
                    @include('frontend.themes.ecommerce.partials.product-card', ['product' => $product])
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>