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
    ];

    if ($category->parent) {
        $crumbs[] = ['label' => $category->parent->name, 'url' => route('shop.category', $category->parent->slug)];
    }

    $crumbs[] = ['label' => $category->name, 'url' => null];
@endphp

@include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <header class="mb-8">
        <h1 class="text-3xl font-extrabold text-zinc-900 sm:text-4xl">{{ $category->name }}</h1>
        @if (filled($category->icon))
            <img src="{{ $category->icon }}" alt="" class="mt-3 h-12 w-12 rounded-xl object-contain">
        @endif
        <p class="mt-3 max-w-2xl text-zinc-600">
            @php($categoryDescription = is_array($category->description) ? ($category->description[app()->getLocale()] ?? reset($category->description)) : $category->description)
            {{ $categoryDescription }}
        </p>
    </header>

    @if ($children->isNotEmpty())
        <div class="mb-8 flex flex-wrap gap-3">
            @foreach ($children as $child)
                <a href="{{ route('shop.category', $child->slug) }}"
                    class="rounded-full border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-primary hover:text-primary">
                    {{ $child->name }}
                </a>
            @endforeach
        </div>
    @endif

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="mb-10">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($section->localizedCards() as $card)
                    <div class="group overflow-hidden rounded-card border border-zinc-100 bg-white shadow-sm">
                        @if ($card['image'])
                            <div class="relative aspect-square overflow-hidden bg-zinc-100">
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" class="h-full w-full object-cover">
                            </div>
                        @endif
                        <div class="p-4">
                            @if ($card['title'])
                                <h3 class="font-semibold text-zinc-900">{{ $card['title'] }}</h3>
                            @endif
                            @if ($card['description'])
                                <p class="mt-1 text-sm text-zinc-500">{{ $card['description'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <section>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900">{{ __('Products in :category', ['category' => $category->name]) }}</h2>
                <p class="mt-1 text-sm text-zinc-500">{{ __(':count products found', ['count' => $products->total()]) }}</p>
            </div>
            <a href="{{ route('shop', ['category' => $category->slug]) }}" class="text-sm font-semibold text-primary hover:underline">
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
            <div class="flex flex-col items-center justify-center rounded-card border border-dashed border-zinc-200 py-24 text-center">
                <h2 class="text-lg font-semibold text-zinc-900">{{ __('No products in this category yet') }}</h2>
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