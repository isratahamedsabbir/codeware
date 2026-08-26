<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="bg-zinc-950 text-zinc-100 antialiased">

    @php
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');
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

    <header class="fixed inset-x-0 top-0 z-20 border-b border-white/10 bg-zinc-950/70 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                @if ($siteIcon)
                    <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-8 w-auto">
                @endif
                <span class="text-lg font-bold tracking-tight text-white">{{ $siteName }}</span>
            </a>
            <a href="{{ route('login') }}"
                class="rounded-full border border-white/20 px-5 py-2 text-sm font-semibold text-white transition hover:border-primary hover:text-primary">
                {{ __('Sign in') }}
            </a>
        </div>
    </header>

    <main>
        @if ($first)
            {{-- Cinematic hero: the first section always renders full-bleed with heavy overlay --}}
            <section class="relative flex min-h-screen items-center justify-center px-6 text-center"
                @if ($first->bg_image) style="background-image: linear-gradient(180deg, rgba(9,9,11,.55), rgba(9,9,11,.92)), url('{{ $first->bg_image }}'); background-size: cover; background-position: center;" @endif>
                <div class="max-w-3xl">
                    @if ($first->localized('title'))
                        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-6xl">{{ $first->localized('title') }}</h1>
                    @endif
                    @if ($first->localized('description'))
                        <p class="mx-auto mt-6 max-w-xl text-lg text-zinc-300">{{ $first->localized('description') }}</p>
                    @endif
                    @if (filled($first->localizedButtons()))
                        <div class="mt-10 flex flex-wrap justify-center gap-4">
                            @foreach ($first->localizedButtons() as $button)
                                <a href="{{ $button['link'] ?? '#' }}"
                                    class="rounded-full px-8 py-3.5 text-sm font-semibold text-white shadow-xl transition hover:-translate-y-0.5"
                                    style="background-color: {{ $button['color'] ?: 'var(--color-primary)' }}">
                                    {{ $button['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                    @if (filled($first->localizedCards()))
                        <div class="mt-16 grid gap-4 sm:grid-cols-3">
                            @foreach ($first->localizedCards() as $card)
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-left backdrop-blur">
                                    @if ($card['image'])
                                        <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" class="mb-3 h-32 w-full rounded-xl object-cover">
                                    @endif
                                    @if ($card['title'])
                                        <h3 class="font-semibold text-white">{{ $card['title'] }}</h3>
                                    @endif
                                    @if ($card['description'])
                                        <p class="mt-1 text-sm text-zinc-400">{{ $card['description'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @else
            <section class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
                <h1 class="text-4xl font-extrabold text-white">{{ $siteName }}</h1>
                <p class="mt-4 max-w-md text-zinc-400">
                    {{ __('Add sections to the "home" page in the CMS to populate this page.') }}
                </p>
            </section>
        @endif

        @foreach ($rest as $index => $section)
            @php $flip = $index % 2 === 1; @endphp
            <section class="border-t border-white/10 px-6 py-24">
                <div class="mx-auto grid max-w-6xl items-center gap-12 md:grid-cols-2">
                    <div class="{{ $flip ? 'md:order-2' : '' }}">
                        @if ($section->localized('title'))
                            <h2 class="text-3xl font-bold tracking-tight text-white">{{ $section->localized('title') }}</h2>
                        @endif
                        @if ($section->localized('description'))
                            <p class="mt-4 text-zinc-400">{{ $section->localized('description') }}</p>
                        @endif
                        @if (filled($section->localizedButtons()))
                            <div class="mt-6 flex flex-wrap gap-3">
                                @foreach ($section->localizedButtons() as $button)
                                    <a href="{{ $button['link'] ?? '#' }}"
                                        class="rounded-full px-6 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                                        style="background-color: {{ $button['color'] ?: 'var(--color-primary)' }}">
                                        {{ $button['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="{{ $flip ? 'md:order-1' : '' }}">
                        @if ($section->image)
                            <img src="{{ $section->image }}" alt="{{ $section->localized('title') }}"
                                class="w-full rounded-3xl border border-white/10 shadow-2xl">
                        @elseif (filled($section->localizedCards()))
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($section->localizedCards() as $card)
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:border-primary/50">
                                        @if ($card['image'])
                                            <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" class="mb-3 h-28 w-full rounded-xl object-cover">
                                        @endif
                                        @if ($card['title'])
                                            <h3 class="font-semibold text-white">{{ $card['title'] }}</h3>
                                        @endif
                                        @if ($card['description'])
                                            <p class="mt-1 text-sm text-zinc-400">{{ $card['description'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endforeach
    </main>

    <footer class="border-t border-white/10 bg-black px-6 py-12">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
            <p class="text-sm text-zinc-500">&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
            @if ($socials->isNotEmpty())
                <div class="flex gap-5">
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
