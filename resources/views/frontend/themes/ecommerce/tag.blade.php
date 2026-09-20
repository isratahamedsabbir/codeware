<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
</head>
<body class="bg-white text-zinc-800 antialiased">

@include('frontend.themes.ecommerce.partials.header')

@php
    $crumbs = [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Shop'), 'url' => route('shop')],
        ['label' => $tag->name, 'url' => null],
    ];
@endphp

@include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <header class="mb-8">
        <h1 class="text-3xl font-extrabold text-zinc-900 sm:text-4xl">
            <span class="text-primary">#</span>{{ $tag->name }}
        </h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('Products labeled with this tag') }}</p>
    </header>

    <section>
        <div class="mb-6 flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900">{{ __('Products tagged :tag', ['tag' => $tag->name]) }}</h2>
                <p class="mt-1 text-sm text-zinc-500">{{ __(':count products found', ['count' => $products->total()]) }}</p>
            </div>
            <a href="{{ route('shop', ['tag' => $tag->slug]) }}" class="text-sm font-semibold text-primary hover:underline">
                {{ __('Filter in shop') }} →
            </a>
        </div>

        @if ($products->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    @include('frontend.themes.ecommerce.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-200 py-24 text-center">
                <h2 class="text-lg font-semibold text-zinc-900">{{ __('No products with this tag yet') }}</h2>
                <a href="{{ route('shop') }}" class="mt-5 rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                    {{ __('View all products') }}
                </a>
            </div>
        @endif
    </section>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
</body>
</html>