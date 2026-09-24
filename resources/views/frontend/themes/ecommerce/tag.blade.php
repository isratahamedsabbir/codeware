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
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Shop'), 'url' => route('shop')],
        ['label' => $tag->name, 'url' => null],
    ];
@endphp

@include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <header class="mb-8">
        <h1 class="text-3xl font-extrabold text-sf-heading sm:text-4xl">
            <span class="text-brand">#</span>{{ $tag->name }}
        </h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('Products labeled with this tag') }}</p>
    </header>

    <section>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
            <div>
                <h2 class="text-2xl font-bold text-sf-heading">{{ __('Products tagged :tag', ['tag' => $tag->name]) }}</h2>
                <p class="mt-1 text-sm text-zinc-500">{{ __(':count products found', ['count' => $products->total()]) }}</p>
            </div>
            <a href="{{ route('shop', ['tag' => $tag->slug]) }}" class="text-sm font-semibold text-brand hover:underline">
                {{ __('Filter in shop') }} →
            </a>
        </div>

        @if ($products->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-5 lg:grid-cols-4">
                @foreach ($products as $product)
                    @include('frontend.themes.ecommerce.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-card border border-dashed border-zinc-200 py-24 text-center">
                <h2 class="text-lg font-semibold text-sf-heading">{{ __('No products with this tag yet') }}</h2>
                <a href="{{ route('shop') }}" class="mt-5 rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                    {{ __('View all products') }}
                </a>
            </div>
        @endif
    </section>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>