<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
</head>
<body class="bg-white text-zinc-800 antialiased">

@include('frontend.themes.ecommerce.partials.header')

@include('frontend.themes.ecommerce.partials.breadcrumbs', [
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => $page->getTranslation('title', 'en', false), 'url' => null],
    ],
])

<main>
    <section class="mx-auto max-w-3xl px-6 py-12 text-center">
        <h1 class="text-4xl font-extrabold text-zinc-900">{{ $page->getTranslation('title', 'en', false) }}</h1>
    </section>

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="border-t border-zinc-100 px-6 py-16">
            <div class="mx-auto max-w-7xl">
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
            </div>
        </section>
    @endforeach

    @if ($block = \App\Support\PageBlocks::for($page->slug))
        <section class="mx-auto max-w-xl px-6 py-16">
            @livewire($block)
        </section>
    @endif
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
</body>
</html>