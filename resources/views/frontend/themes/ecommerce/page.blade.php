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

    <header class="sticky top-0 z-20 border-b border-zinc-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                @if ($siteIcon)
                    <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-8 w-auto">
                @endif
                <span class="text-lg font-bold text-zinc-900">{{ $siteName }}</span>
            </a>

            <nav class="hidden items-center gap-6 md:flex">
                @foreach ($menuItems ?? [] as $menuItem)
                    <a href="{{ url($menuItem->url) }}"
                        class="text-sm font-medium {{ url($menuItem->url) === url()->current() ? 'text-primary' : 'text-zinc-600 hover:text-zinc-900' }}">
                        {{ $menuItem->label }}
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
        <section class="mx-auto flex min-h-[24rem] max-w-2xl flex-col items-center justify-center px-6 text-center">
            <h1 class="text-4xl font-extrabold text-zinc-900">{{ $page->getTranslation('title', 'en', false) }}</h1>
        </section>

        @foreach ($sections as $section)
            @continue(blank($section->localizedCards()))

            <section id="{{ $section->name }}" class="mx-auto max-w-7xl border-t border-zinc-100 px-6 py-16">
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
            </section>
        @endforeach

        @if ($block = \App\Support\PageBlocks::for($page->slug))
            <section class="mx-auto max-w-xl px-6 py-16">
                @livewire($block)
            </section>
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
