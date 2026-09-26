<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    <link rel="stylesheet" href="{{ asset('themes/portfolio/style.css') }}">
    <style>[x-cloak]{display:none!important}</style>
    @include('partials.custom-code-head')
</head>
<body class="theme-portfolio antialiased">

    @php
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');
        $contactEmail = \App\Models\Setting::get('contact_email');
        $socialIcons = [
            'facebook' => 'FB',
            'twitter' => 'X',
            'instagram' => 'IG',
            'youtube' => 'YT',
            'linkedin' => 'IN',
            'tiktok' => 'TT',
        ];
        $socials = collect($socialIcons)
            ->map(fn ($abbr, $platform) => ['url' => \App\Models\SocialLink::url($platform), 'abbr' => $abbr])
            ->filter(fn ($social) => filled($social['url']))
            ->values();
    @endphp

    @include('frontend.themes.portfolio.partials.header')

    <main>
        {{-- Hero --}}
        <section class="pf-grid-bg relative flex min-h-[70vh] items-center overflow-hidden px-6 pt-24">
            <div class="mx-auto max-w-4xl text-center">
                <h1 data-typewriter class="pf-heading text-4xl font-extrabold leading-tight tracking-tight sm:text-6xl" style="visibility:hidden">{{ $page->getTranslation('title', 'en', false) }}</h1>

                @if ($socials->isNotEmpty() || $contactEmail)
                    <div class="mt-10 flex justify-center gap-3">
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
        </section>

        @foreach ($sections as $section)
            @continue(blank($section->localizedCards()))

            <section id="{{ $section->name }}" class="border-t border-(--pf-border) px-6 py-24">
                <div class="mx-auto max-w-6xl">
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

    @include('frontend.themes.portfolio.partials.footer')

    <livewire:frontend.chat-widget />

    @fluxScripts
    <script src="{{ asset('themes/portfolio/script.js') }}" defer></script>
@include('partials.custom-code-body')
</body>
</html>
