{{--
    Portfolio theme footer — owned by the one-pager (home.blade.php), which is
    the only page this theme ships. Kept as a partial so the markup lives in one
    place rather than inline in the one view that uses it.

    Expects $siteName and $socials from the including view. $socials is a list of
    ['platform' => 'github', 'label' => 'GitHub', 'url' => '...'] entries (already
    filtered down to the platforms that actually have a URL, see
    SocialLink::url()).

    Three columns rather than one centred line: a single copyright string with a
    row of icons is the least informative thing a site can end on, while the
    section anchors and the stack summary give a visitor somewhere to go next.
--}}
@php
    $footerSections = \App\Support\Frontend::portfolioMenuItems()
        ->filter(fn ($item) => str_starts_with((string) $item->url, '#'))
        ->values();
@endphp

<footer class="border-t border-(--pf-border) px-6 pt-16 pb-8">
    <div class="mx-auto max-w-6xl">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr]">
            {{-- Who this is --}}
            <div>
                <a href="{{ url('/') }}" class="pf-heading flex items-center gap-2.5">
                    <span class="pf-mono flex h-9 w-9 items-center justify-center rounded-lg bg-(--pf-primary-soft) text-sm font-bold text-(--pf-primary)">
                        {{ \App\Support\PortfolioProfile::monogram($siteName) }}
                    </span>
                    <span class="text-base font-bold tracking-tight">{{ $siteName }}</span>
                </a>

                <p class="mt-4 max-w-sm text-sm leading-relaxed text-(--pf-text-muted)">
                    {{ \App\Support\PortfolioProfile::hero()['tagline'] }}
                </p>
            </div>

            {{-- Section anchors, so a visitor who scrolled past something has a
                 way back to it. Same list the header uses. --}}
            @if ($footerSections->isNotEmpty())
                <div>
                    <h2 class="pf-mono text-[10px] font-semibold tracking-wider text-(--pf-text-muted) uppercase">Sections</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($footerSections as $item)
                            <li>
                                {{-- No data-pf-nav-link here: the scroll spy marks
                                     "the section you are in", and a footer link
                                     lighting up while you are halfway up the page
                                     is a worse cue than none. The header already
                                     carries the live indicator. --}}
                                <a href="{{ url('/') }}{{ $item->url }}"
                                    class="pf-mono text-xs text-(--pf-text-muted) transition-colors hover:text-(--pf-primary)">
                                    {{ $item->label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Where to find me --}}
            <div>
                <h2 class="pf-mono text-[10px] font-semibold tracking-wider text-(--pf-text-muted) uppercase">Elsewhere</h2>
                <div class="mt-4">
                    @include('frontend.themes.portfolio.partials.social-links', ['variant' => 'row'])
                </div>
            </div>
        </div>

        <div class="mt-14 flex flex-col items-center gap-4 border-t border-(--pf-border) pt-7 sm:flex-row sm:justify-between">
            <p class="pf-mono text-[11px] text-(--pf-text-muted)">
                &copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}
            </p>

            <a href="#home" class="pf-mono inline-flex items-center gap-2 text-[11px] text-(--pf-text-muted) transition-colors hover:text-(--pf-primary)">
                {{ __('Back to top') }}
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5" />
                    <polyline points="5 12 12 5 19 12" />
                </svg>
            </a>
        </div>
    </div>
</footer>
