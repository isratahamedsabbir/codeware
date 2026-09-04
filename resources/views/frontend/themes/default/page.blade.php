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
            'facebook' => 'Facebook',
            'twitter' => 'Twitter / X',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            'linkedin' => 'LinkedIn',
        ])->map(fn ($label, $platform) => ['url' => \App\Models\SocialLink::url($platform), 'label' => $label])
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

            <nav class="hidden items-center gap-6 md:flex">
                @foreach ($navPages ?? [] as $navPage)
                    <a href="{{ $navPage->slug === 'home' ? route('home') : route('page', $navPage->slug) }}"
                        class="text-sm font-medium {{ ($currentSlug ?? null) === $navPage->slug ? 'text-primary' : 'text-zinc-600 hover:text-zinc-900' }}">
                        {{ $navPage->getTranslation('title', 'en', false) }}
                    </a>
                @endforeach
            </nav>

            <a href="{{ route('login') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                {{ __('Sign in') }}
            </a>
        </div>
    </header>

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

    <footer class="border-t border-zinc-100 bg-zinc-50 px-6 py-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
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
