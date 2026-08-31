<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('themes/portfolio/style.css') }}">
</head>
<body class="theme-portfolio antialiased">

    @php
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');
        $contactEmail = \App\Models\Setting::get('contact_email');
        $socialIcons = [
            'facebook_url' => 'FB',
            'twitter_url' => 'X',
            'instagram_url' => 'IG',
            'youtube_url' => 'YT',
            'linkedin_url' => 'IN',
            'tiktok_url' => 'TT',
        ];
        $socials = collect($socialIcons)
            ->map(fn ($abbr, $key) => ['url' => \App\Models\Setting::get($key), 'abbr' => $abbr])
            ->filter(fn ($social) => filled($social['url']))
            ->values();
        $first = $sections->first();
        $rest = $sections->skip(1)->values();
        $badge = $first?->metadataMap()['badge'] ?? null;
    @endphp

    <header class="fixed inset-x-0 top-0 z-20 border-b border-(--pf-border) bg-(--pf-bg)/80 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="pf-heading flex items-center gap-2">
                @if ($siteIcon)
                    <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-7 w-auto">
                @endif
                <span class="text-base font-bold tracking-tight">{{ $siteName }}</span>
            </a>

            <nav class="hidden items-center gap-8 md:flex">
                @foreach ($menuItems ?? [] as $menuItem)
                    <a href="{{ url($menuItem->url) }}"
                        class="pf-nav-link pf-mono text-xs uppercase tracking-widest {{ url($menuItem->url) === url()->current() ? 'is-active' : '' }}">
                        {{ $menuItem->label }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                <div class="pf-lang-pill pf-mono text-xs font-semibold uppercase">
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="{{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</a>
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'bn']) }}" class="{{ app()->getLocale() === 'bn' ? 'is-active' : '' }}">BN</a>
                </div>

                <button type="button" data-pf-theme-toggle aria-label="Toggle light / dark theme" class="pf-theme-toggle">
                    <svg class="pf-icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="4" />
                        <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41" />
                    </svg>
                    <svg class="pf-icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <main>
        @if ($first)
            {{-- Hero --}}
            <section class="pf-grid-bg relative flex min-h-screen items-center overflow-hidden px-6 pt-24">
                <div class="mx-auto grid w-full max-w-6xl items-center gap-12 lg:grid-cols-[1fr_360px]">
                    <div>
                        @if ($badge)
                            <span class="pf-badge pf-mono mb-6 rounded-full px-4 py-2 text-xs font-medium">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="16 18 22 12 16 6" />
                                    <polyline points="8 6 2 12 8 18" />
                                </svg>
                                {{ $badge }}
                            </span>
                        @endif

                        <h1 data-typewriter class="pf-heading text-4xl font-extrabold leading-tight tracking-tight sm:text-6xl" style="visibility:hidden">{{ $first->localized('title') }}</h1>

                        @if ($first->localized('description'))
                            <p class="mt-6 max-w-xl text-lg text-(--pf-text-muted)">{{ $first->localized('description') }}</p>
                        @endif

                        @if ($socials->isNotEmpty() || $contactEmail)
                            <div class="mt-10 flex gap-3">
                                @foreach ($socials as $social)
                                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['abbr'] }}"
                                        class="pf-social-icon pf-mono text-[11px] font-bold">
                                        {{ $social['abbr'] }}
                                    </a>
                                @endforeach
                                @if ($contactEmail)
                                    <a href="mailto:{{ $contactEmail }}" aria-label="Email" class="pf-social-icon">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="5" width="18" height="14" rx="2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($first->image)
                        <div class="relative hidden lg:block">
                            <div class="pf-glow absolute -inset-8 -z-10"></div>
                            <div class="pf-photo-frame aspect-square overflow-hidden rounded-3xl p-2 shadow-2xl">
                                <img src="{{ $first->image }}" alt="{{ $first->localized('title') }}" class="h-full w-full rounded-2xl object-cover">
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @else
            <section class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
                <h1 class="pf-heading text-4xl font-bold">{{ $page->getTranslation('title', 'en', false) }}</h1>
                <p class="mt-4 max-w-md text-(--pf-text-muted)">
                    {{ __('Add sections to the ":page" page in the CMS to populate this page.', ['page' => $page->getTranslation('title', 'en', false)]) }}
                </p>
            </section>
        @endif

        @foreach ($rest as $index => $section)
            @php $hasCards = filled($section->localizedCards()); @endphp

            <section id="{{ $section->name }}" class="border-t border-(--pf-border) px-6 py-24">
                <div class="mx-auto max-w-6xl">
                    @if ($section->localized('title') || $section->localized('description'))
                        <div class="mb-14 max-w-xl">
                            <p class="pf-mono mb-3 text-xs text-(--pf-text-muted)">// {{ sprintf('%02d', $index + 1) }}</p>
                            @if ($section->localized('title'))
                                <h2 class="pf-heading text-3xl font-bold tracking-tight sm:text-4xl">{{ $section->localized('title') }}</h2>
                            @endif
                            @if ($section->localized('description'))
                                <p class="mt-4 text-(--pf-text-muted)">{{ $section->localized('description') }}</p>
                            @endif
                        </div>
                    @endif

                    @if ($section->image && ! $hasCards)
                        <img src="{{ $section->image }}" alt="{{ $section->localized('title') }}" class="pf-card w-full rounded-xl p-2">
                    @endif

                    {{-- Work / project grid --}}
                    @if ($hasCards)
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($section->localizedCards() as $card)
                                <div class="pf-card group overflow-hidden rounded-xl">
                                    @if ($card['image'])
                                        <div class="aspect-4/3 overflow-hidden">
                                            <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                        </div>
                                    @endif
                                    <div class="p-5">
                                        @if ($card['title'])
                                            <h3 class="pf-heading pf-mono font-semibold">{{ $card['title'] }}</h3>
                                        @endif
                                        @if ($card['description'])
                                            <p class="mt-2 text-sm text-(--pf-text-muted) line-clamp-2">{{ $card['description'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endforeach

        @if ($block = \App\Support\PageBlocks::for($page->slug))
            <section class="px-6 py-16">
                <div class="pf-card pf-contact-form mx-auto max-w-xl rounded-2xl p-6 sm:p-8">
                    @livewire($block)
                </div>
            </section>
        @endif
    </main>

    <footer class="border-t border-(--pf-border) px-6 py-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
            <p class="pf-mono text-xs text-(--pf-text-muted)">&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
            @if ($socials->isNotEmpty())
                <div class="flex gap-3">
                    @foreach ($socials as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['abbr'] }}" class="pf-social-icon pf-mono text-[11px] font-bold">
                            {{ $social['abbr'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </footer>

    <livewire:frontend.chat-widget />

    @fluxScripts
    <script src="{{ asset('themes/portfolio/script.js') }}" defer></script>
</body>
</html>
