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
    @endphp

    <header class="sticky top-0 z-20 border-b border-zinc-100 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                @if ($siteIcon)
                    <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-8 w-auto">
                @endif
                <span class="text-lg font-bold text-zinc-900">{{ $siteName }}</span>
            </a>
            <a href="{{ route('login') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                {{ __('Sign in') }}
            </a>
        </div>
    </header>

    <main>
        @forelse ($sections as $section)
            @php
                $hasCards = filled($section->localizedCards());
                $hasButtons = filled($section->localizedButtons());
            @endphp

            @if ($section->bg_image)
                {{-- Hero-style section: full-bleed background image with a dark overlay --}}
                <section class="relative flex min-h-[26rem] items-center justify-center bg-cover bg-center px-6 py-20 text-center"
                    style="background-image: url('{{ $section->bg_image }}')">
                    <div class="absolute inset-0 bg-zinc-900/60"></div>
                    <div class="relative max-w-2xl">
                        @if ($section->localized('title'))
                            <h1 class="text-3xl font-extrabold text-white sm:text-5xl">{{ $section->localized('title') }}</h1>
                        @endif
                        @if ($section->localized('description'))
                            <p class="mt-4 text-lg text-zinc-100">{{ $section->localized('description') }}</p>
                        @endif
                        @if ($hasButtons)
                            <div class="mt-8 flex flex-wrap justify-center gap-3">
                                @foreach ($section->localizedButtons() as $button)
                                    <a href="{{ $button['link'] ?? '#' }}"
                                        class="rounded-lg px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:opacity-90"
                                        style="background-color: {{ $button['color'] ?: 'var(--color-primary)' }}">
                                        {{ $button['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @else
                <section class="mx-auto max-w-6xl px-6 py-16">
                    <div class="grid items-center gap-10 {{ $section->image ? 'md:grid-cols-2' : '' }}">
                        <div class="{{ $section->image ? 'order-2 md:order-1' : 'mx-auto max-w-2xl text-center' }}">
                            @if ($section->localized('title'))
                                <h2 class="text-2xl font-bold text-zinc-900 sm:text-3xl">{{ $section->localized('title') }}</h2>
                            @endif
                            @if ($section->localized('description'))
                                <p class="mt-4 text-zinc-600">{{ $section->localized('description') }}</p>
                            @endif
                            @if ($hasButtons)
                                <div class="mt-6 flex flex-wrap {{ $section->image ? '' : 'justify-center' }} gap-3">
                                    @foreach ($section->localizedButtons() as $button)
                                        <a href="{{ $button['link'] ?? '#' }}"
                                            class="rounded-lg px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                                            style="background-color: {{ $button['color'] ?: 'var(--color-primary)' }}">
                                            {{ $button['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if ($section->image)
                            <div class="order-1 md:order-2">
                                <img src="{{ $section->image }}" alt="{{ $section->localized('title') }}" class="w-full rounded-2xl shadow-lg">
                            </div>
                        @endif
                    </div>

                    @if ($hasCards)
                        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
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
                    @endif
                </section>
            @endif
        @empty
            <section class="mx-auto flex max-w-2xl flex-col items-center px-6 py-32 text-center">
                <h1 class="text-3xl font-bold text-zinc-900">{{ $siteName }}</h1>
                <p class="mt-4 text-zinc-500">
                    {{ __('Add sections to the "home" page in the CMS to populate this page.') }}
                </p>
            </section>
        @endforelse
    </main>

    <footer class="border-t border-zinc-100 bg-zinc-50 px-6 py-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
            <p class="text-sm text-zinc-500">&copy; {{ now()->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
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

    @fluxScripts
</body>
</html>
