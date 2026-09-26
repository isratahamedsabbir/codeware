<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    @include('partials.custom-code-head')
</head>
<body class="bg-white text-zinc-800 antialiased">

@include('frontend.themes.default.partials.header', [
    'navPages' => $navPages ?? [],
    'currentSlug' => $currentSlug ?? null,
    'showVendorLogin' => $showVendorLogin ?? false,
    'showDeliveryLogin' => $showDeliveryLogin ?? false,
])

<main>
        <section class="mx-auto max-w-2xl px-6 py-24 text-center">
            <h1 class="text-3xl font-bold text-zinc-900 sm:text-4xl">{{ $page->getTranslation('title', 'en', false) }}</h1>
        </section>

        @foreach ($sections as $section)
            @continue(blank($section->localizedCards()))

            <section id="{{ $section->name }}" class="border-t border-zinc-100 px-6 py-16">
                <div class="mx-auto max-w-6xl">
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($section->localizedCards() as $card)
                            <div class="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm transition hover:shadow-md">
                                @if ($card['image'])
                                    <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" class="mb-4 h-40 w-full rounded-xl object-cover">
                                @endif
                                @if ($card['title'])
                                    <h3 class="text-lg font-semibold text-zinc-900">{{ $card['title'] }}</h3>
                                @endif
                                @if ($card['description'])
                                    <p class="mt-2 text-sm text-zinc-600">{{ $card['description'] }}</p>
                                @endif
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

@include('frontend.themes.default.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>
