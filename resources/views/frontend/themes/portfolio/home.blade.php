<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="bg-white text-zinc-900 antialiased">

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

    <header class="fixed inset-x-0 top-0 z-20 mix-blend-difference">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-white">
                @if ($siteIcon)
                    <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-7 w-auto invert">
                @endif
                <span class="text-sm font-semibold uppercase tracking-[0.2em]">{{ $siteName }}</span>
            </a>
            <a href="{{ route('login') }}" class="text-sm font-medium uppercase tracking-widest text-white hover:opacity-70">
                {{ __('Sign in') }}
            </a>
        </div>
    </header>

    <main>
        @if ($first)
            <section class="flex min-h-screen flex-col justify-center px-6">
                <div class="mx-auto w-full max-w-5xl">
                    @if ($first->localized('title'))
                        <h1 class="text-5xl font-bold leading-[1.05] tracking-tight sm:text-8xl">
                            {{ $first->localized('title') }}
                        </h1>
                    @endif
                    @if ($first->localized('description'))
                        <p class="mt-8 max-w-xl text-lg text-zinc-500">{{ $first->localized('description') }}</p>
                    @endif
                    @if (filled($first->localizedButtons()))
                        <div class="mt-10 flex flex-wrap gap-4">
                            @foreach ($first->localizedButtons() as $button)
                                <a href="{{ $button['link'] ?? '#' }}"
                                    class="inline-flex items-center gap-2 border-b-2 pb-1 text-sm font-semibold uppercase tracking-widest transition hover:gap-3"
                                    style="border-color: {{ $button['color'] ?: 'var(--color-primary)' }}; color: {{ $button['color'] ?: 'var(--color-primary)' }}">
                                    {{ $button['label'] }} &rarr;
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @else
            <section class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
                <h1 class="text-5xl font-bold tracking-tight">{{ $siteName }}</h1>
                <p class="mt-4 max-w-md text-zinc-500">
                    {{ __('Add sections to the "home" page in the CMS to populate this page.') }}
                </p>
            </section>
        @endif

        @foreach ($rest as $section)
            @php $hasCards = filled($section->localizedCards()); @endphp

            <section class="border-t border-zinc-100 px-6 py-24">
                <div class="mx-auto max-w-5xl">
                    @if ($section->localized('title') || $section->localized('description'))
                        <div class="mb-14 max-w-xl">
                            @if ($section->localized('title'))
                                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $section->localized('title') }}</h2>
                            @endif
                            @if ($section->localized('description'))
                                <p class="mt-4 text-zinc-500">{{ $section->localized('description') }}</p>
                            @endif
                            @if (filled($section->localizedButtons()))
                                <div class="mt-6 flex flex-wrap gap-4">
                                    @foreach ($section->localizedButtons() as $button)
                                        <a href="{{ $button['link'] ?? '#' }}"
                                            class="inline-flex items-center gap-2 border-b-2 pb-1 text-sm font-semibold uppercase tracking-widest transition hover:gap-3"
                                            style="border-color: {{ $button['color'] ?: 'var(--color-primary)' }}; color: {{ $button['color'] ?: 'var(--color-primary)' }}">
                                            {{ $button['label'] }} &rarr;
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($section->image && ! $hasCards)
                        <img src="{{ $section->image }}" alt="{{ $section->localized('title') }}" class="w-full">
                    @endif

                    {{-- Work / project grid --}}
                    @if ($hasCards)
                        <div class="grid gap-px sm:grid-cols-2">
                            @foreach ($section->localizedCards() as $card)
                                <div class="group relative aspect-[4/3] overflow-hidden bg-zinc-100">
                                    @if ($card['image'])
                                        <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @endif
                                    <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/70 via-black/0 to-transparent p-6 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                        @if ($card['title'])
                                            <h3 class="text-lg font-semibold text-white">{{ $card['title'] }}</h3>
                                        @endif
                                        @if ($card['description'])
                                            <p class="mt-1 text-sm text-zinc-200 line-clamp-2">{{ $card['description'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    </main>

    <footer class="border-t border-zinc-100 px-6 py-10">
        <div class="mx-auto flex max-w-5xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
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

    <livewire:frontend.chat-widget />

    @fluxScripts
</body>
</html>
