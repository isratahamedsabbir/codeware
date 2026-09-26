{{--
    Portfolio theme header — the single-page nav, shared by home.blade.php and
    page.blade.php.

    Expects $siteName and $siteIcon from the including view. The nav itself is
    resolved here from the portfolio-specific menu (see PortfolioMenuSeeder)
    rather than the shared "frontend" menu the ecommerce theme uses: the
    portfolio is one page, so its items are section anchors ("#projects",
    "#skills", ...) that only mean anything on that page, and keeping them in
    their own menu means switching themes never reshuffles either one.

    A bare "#fragment" is anchored to the site root rather than left as-is:
    url('#skills') returns the fragment unchanged, which on a secondary page
    like /about would resolve to /about#skills and find nothing. Prefixing the
    root keeps the link landing on the one-pager's section from anywhere.
--}}
@php
    $currentUrl = url()->current();
    $portfolioMenu = \App\Support\Frontend::portfolioMenuItems()
        ->map(function ($item) use ($currentUrl) {
            $url = (string) $item->url;
            $isAnchor = str_starts_with($url, '#');

            return [
                'label' => $item->label,
                'href' => $isAnchor ? url('/').$url : url($url),
                'section' => $isAnchor ? substr($url, 1) : null,
                'is_active' => ! $isAnchor && url($url) === $currentUrl,
            ];
        })
        ->values();
@endphp

<header x-data="{ open: false }" x-on:keydown.escape.window="open = false"
    class="pf-header fixed inset-x-0 top-0 z-20 border-b border-(--pf-border) bg-(--pf-bg)/80 backdrop-blur-md">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="{{ url('/') }}" class="pf-heading flex items-center gap-2">
            @if ($siteIcon)
                <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-7 w-auto">
            @endif
            <span class="text-base font-bold tracking-tight">{{ $siteName }}</span>
        </a>

        @if ($portfolioMenu->isNotEmpty())
            <nav class="hidden items-center gap-8 md:flex">
                @foreach ($portfolioMenu as $item)
                    <a href="{{ $item['href'] }}" @if ($item['section']) data-pf-nav-link="{{ $item['section'] }}" @endif
                        class="pf-nav-link pf-mono text-xs uppercase tracking-widest {{ $item['is_active'] ? 'is-active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        @endif

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

            @if ($portfolioMenu->isNotEmpty())
                <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open ? 'true' : 'false'"
                    aria-controls="pf-mobile-nav" aria-label="Menu" class="pf-nav-toggle md:hidden">
                    <svg x-show="! open" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                    <svg x-cloak x-show="open" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="6" y1="6" x2="18" y2="18" />
                        <line x1="18" y1="6" x2="6" y2="18" />
                    </svg>
                </button>
            @endif
        </div>
    </div>

    {{-- The same nav, stacked — the inline one above is md:flex only, and on a
         single-page layout a phone needs it to reach any section but the first. --}}
    @if ($portfolioMenu->isNotEmpty())
        <nav id="pf-mobile-nav" x-show="open" x-cloak x-on:click="open = false"
            class="pf-mobile-nav border-t border-(--pf-border) md:hidden">
            @foreach ($portfolioMenu as $item)
                <a href="{{ $item['href'] }}" @if ($item['section']) data-pf-nav-link="{{ $item['section'] }}" @endif
                    class="pf-mobile-nav-link pf-mono {{ $item['is_active'] ? 'is-active' : '' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    @endif
</header>
