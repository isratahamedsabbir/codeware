<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="bg-white text-zinc-800 antialiased">

    @php
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteIcon = \App\Models\Setting::get('site_icon');
        $socials = collect([
            'facebook_url' => 'Facebook',
            'twitter_url' => 'Twitter / X',
            'instagram_url' => 'Instagram',
            'youtube_url' => 'YouTube',
            'linkedin_url' => 'LinkedIn',
        ])->map(fn ($label, $key) => ['url' => \App\Models\Setting::get($key), 'label' => $label])
          ->filter(fn ($social) => filled($social['url']));
        $first = $sections->first();
        $rest = $sections->skip(1);
    @endphp

    <header class="sticky top-0 z-20 border-b border-zinc-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                @if ($siteIcon)
                    <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-8 w-auto">
                @endif
                <span class="text-lg font-bold text-zinc-900">{{ $siteName }}</span>
            </a>

            <nav class="hidden items-center gap-6 md:flex">
                @foreach ($navPages ?? [] as $navPage)
                    <a href="{{ $navPage->slug === 'home' ? route('home') : route('page', $navPage->slug) }}"
                        class="text-sm font-medium {{ ($currentSlug ?? null) === $navPage->slug ? 'text-primary' : 'text-zinc-600 hover:text-zinc-900' }}">
                        {{ $navPage->getTranslation('title', 'en', false) }}
                    </a>
                @endforeach
            </nav>

            <a href="{{ route('login') }}"
                class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                {{ __('Sign in') }}
            </a>
        </div>
    </header>

    <main>
        @if ($first)
            {{-- Hero banner --}}
            <section
                @if ($first->bg_image)
                    class="relative flex min-h-[30rem] items-center justify-center bg-cover bg-center px-6 py-24 text-center"
                    style="background-image: url('{{ $first->bg_image }}')"
                @else
                    class="relative flex min-h-[30rem] items-center justify-center px-6 py-24 text-center bg-gradient-to-br from-zinc-50 to-zinc-100"
                @endif
            >
                @if ($first->bg_image)
                    <div class="absolute inset-0 bg-zinc-900/45"></div>
                @endif
                <div class="relative max-w-2xl">
                    @if ($first->localized('title'))
                        <h1 class="text-4xl font-extrabold tracking-tight sm:text-6xl {{ $first->bg_image ? 'text-white' : 'text-zinc-900' }}">
                            {{ $first->localized('title') }}
                        </h1>
                    @endif
                    @if ($first->localized('description'))
                        <p class="mx-auto mt-5 max-w-xl text-lg {{ $first->bg_image ? 'text-zinc-100' : 'text-zinc-600' }}">
                            {{ $first->localized('description') }}
                        </p>
                    @endif
                </div>
            </section>
        @else
            <section class="mx-auto flex min-h-[30rem] max-w-2xl flex-col items-center justify-center px-6 text-center">
                <h1 class="text-4xl font-extrabold text-zinc-900">{{ $page->getTranslation('title', 'en', false) }}</h1>
                <p class="mt-4 text-zinc-500">
                    {{ __('Add sections to the ":page" page in the CMS to populate this page.', ['page' => $page->getTranslation('title', 'en', false)]) }}
                </p>
            </section>
        @endif

        @foreach ($rest as $section)
            @php
                $hasCards = filled($section->localizedCards());
            @endphp

            <section class="mx-auto max-w-7xl px-6 py-16">
                @if ($section->localized('title') || $section->localized('description'))
                    <div class="mx-auto mb-10 max-w-2xl text-center">
                        @if ($section->localized('title'))
                            <h2 class="text-2xl font-bold text-zinc-900 sm:text-3xl">{{ $section->localized('title') }}</h2>
                        @endif
                        @if ($section->localized('description'))
                            <p class="mt-3 text-zinc-500">{{ $section->localized('description') }}</p>
                        @endif
                    </div>
                @endif

                @if ($section->image && ! $hasCards)
                    <img src="{{ $section->image }}" alt="{{ $section->localized('title') }}" class="mx-auto w-full max-w-4xl rounded-2xl shadow-lg">
                @endif

                {{-- Product-style card grid --}}
                @if ($hasCards)
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
                @endif
            </section>
        @endforeach

        @if ($block = \App\Support\PageBlocks::for($page->slug))
            @livewire($block)
        @endif
    </main>

    <footer class="border-t border-zinc-100 bg-zinc-50 px-6 py-10">
        <div class="mx-auto flex max-w-7xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
            <p class="text-sm text-zinc-500">&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
            @if ($socials->isNotEmpty())
                <div class="flex gap-4">
                    @foreach ($socials as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-sm text-zinc-500 hover:text-primary">
                            {{ $social['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </footer>

    <livewire:frontend.chat-widget />

    @fluxScripts
</body>
</html>
