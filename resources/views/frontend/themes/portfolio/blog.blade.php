<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    <link rel="preload" href="{{ asset('fonts/instrument-sans-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('themes/portfolio/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/portfolio/style.css') }}">
    <style>[x-cloak]{display:none!important}</style>
    @include('partials.custom-code-head')
</head>
<body class="theme-portfolio antialiased">

    @php
        // The header and footer partials are shared with the one-pager and both
        // read these, so they are resolved here rather than in each partial.
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');
        $socials = \App\Support\PortfolioSocials::all();
    @endphp

    @include('frontend.themes.portfolio.partials.header')

    <main>
        <section class="px-6 pt-28 pb-12 sm:pt-32">
            <div class="mx-auto max-w-6xl">
                {{-- A back link rather than a breadcrumb trail: on this theme the
                     one-pager is the only other page, so "â† Back" says where you
                     actually came from. --}}
                <a href="{{ route('home') }}" class="pf-mono text-xs text-(--pf-text-muted) transition hover:text-(--pf-primary)">
                    &larr; {{ __('Back') }}
                </a>

                <div class="mt-8 max-w-2xl">
                    <span class="pf-eyebrow pf-mono">{{ __('Writing') }}</span>
                    <h1 class="pf-heading mt-5 text-4xl font-bold sm:text-5xl">{{ __('Notes from the work') }}</h1>
                    <p class="mt-4 text-(--pf-text-muted)">{{ __('Things worth writing down while building them.') }}</p>

                    {{-- How much there is to read, and whether this view is the
                         whole of it. On a filtered view the total belongs to the
                         archive, not to the one category on screen, so it counts
                         what is actually listed. --}}
                    @if ($posts->total() > 0)
                        <p class="pf-mono mt-5 text-[11px] text-(--pf-text-muted)">
                            @if (request()->query('category'))
                                {{ trans_choice(':count note in this category|:count notes in this category', $posts->total(), ['count' => $posts->total()]) }}
                            @else
                                {{ trans_choice(':count note published|:count notes published', $posts->total(), ['count' => $posts->total()]) }}
                            @endif
                        </p>
                    @endif
                </div>

                {{-- Categories only when there are any. The filter row with one
                     entry in it is a control that navigates nowhere. --}}
                @if ($categories->isNotEmpty())
                    <nav aria-label="{{ __('Blog categories') }}" class="mt-10 flex flex-wrap items-center gap-2">
                        <a href="{{ route('blog') }}"
                            class="pf-chip gap-1.5 px-3.5 py-1.5 text-xs font-medium transition {{ request()->query('category') ? 'hover:text-(--pf-primary)' : 'border-(--pf-primary)! bg-(--pf-primary-soft)! text-(--pf-primary)!' }}">
                            {{ __('All') }}
                        </a>
                        @foreach ($categories as $category)
                            {{-- A post's category slug, like the post's own, lives on
                                 the paired Page (the controller filters on
                                 category.page.slug), so this is not $category->slug. --}}
                            <a href="{{ route('blog', ['category' => $category->page->slug]) }}"
                                class="pf-chip gap-1.5 px-3.5 py-1.5 text-xs font-medium transition {{ request()->query('category') === $category->page->slug ? 'border-(--pf-primary)! bg-(--pf-primary-soft)! text-(--pf-primary)!' : 'hover:text-(--pf-primary)' }}">
                                {{ $category->name }}
                                {{-- The count is already loaded for the nav's own sake
                                     (the controller withCounts it), so printing it
                                     turns a bare filter into a map of where the
                                     writing actually is. --}}
                                <span class="pf-mono text-[10px] opacity-60">{{ $category->posts_count }}</span>
                            </a>
                        @endforeach
                    </nav>
                @endif
            </div>
        </section>

        <section class="px-6 pb-20">
            <div class="mx-auto max-w-6xl">
                {{-- Cards rather than a flat list: on a portfolio the writing
                     page is a second body of work, so each post should read as a
                     piece you could pick up and skim, not a row in a ledger.
                     Images sit on top at a consistent 16/10 so the row of cards
                     stays even whether or not a post has one. --}}
                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($posts as $post)
                        {{-- One link per card, stretched over all of it.

                             The title link carries a ::after that covers the card, so
                             the whole thing is the hit target while the heading stays
                             a real heading containing a real link - which is both the
                             accessible pattern for a linked heading and one tab stop
                             per post instead of three. Without it the image, the title
                             and "Read more" would each want their own link, and
                             "Read more" would end up looking like a button while
                             being a span. --}}
                        <article data-pf-reveal
                            class="pf-card pf-card-hover group relative flex flex-col overflow-hidden">
                            <div class="pf-shot block">
                                @if (filled($post->featured_image))
                                    <img src="{{ $post->featured_image }}" alt="" width="480" height="300"
                                        class="h-full w-full object-cover" loading="lazy" decoding="async">
                                @else
                                    <span class="pf-monogram h-full w-full text-2xl">{{ \Illuminate\Support\Str::substr($post->title, 0, 2) }}</span>
                                @endif
                            </div>

                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Lifted above the stretched link, or the filter
                                         would be unreachable underneath it. --}}
                                    @if ($post->category?->page && $post->category->status === 'active')
                                        <a href="{{ route('blog', ['category' => $post->category->page->slug]) }}"
                                            class="pf-mono relative z-10 text-[11px] font-semibold uppercase tracking-wider text-(--pf-primary) hover:underline">
                                            {{ $post->category->name }}
                                        </a>
                                        {{-- The dot belongs to the pair, not to the
                                             date: a post with no category would
                                             otherwise open on a stray separator. --}}
                                        @if ($post->published_at)
                                            <span class="pf-mono text-[11px] text-(--pf-text-muted)" aria-hidden="true">&middot;</span>
                                        @endif
                                    @endif
                                    @if ($post->published_at)
                                        <time datetime="{{ $post->published_at->toDateString() }}"
                                            class="pf-mono text-[11px] text-(--pf-text-muted)">
                                            {{ $post->published_at->toDisplay() }}
                                        </time>
                                    @endif
                                </div>

                                <h2 class="pf-heading mt-2 text-lg font-bold leading-snug">
                                    <a href="{{ route('blog.post', $post->slug) }}"
                                        class="transition-colors after:absolute after:inset-0 after:content-[''] group-hover:text-(--pf-primary)">
                                        {{ $post->title }}
                                    </a>
                                </h2>

                                @if (filled($post->description))
                                    {{-- Capped for the same reason the ecommerce grid caps
                                         it: a long lead belongs on the post, and every
                                         card on the page would otherwise carry a
                                         paragraph nobody is going to read in place.
                                         Rendered as HTML when it is short enough to
                                         keep its own formatting, and as trimmed
                                         plain text when it is not. --}}
                                    @php
                                        $postDescriptionHtml = (string) (is_array($post->description) ? ($post->description[app()->getLocale()] ?? reset($post->description)) : $post->description);
                                        $postDescriptionText = trim(html_entity_decode(strip_tags($postDescriptionHtml)));
                                    @endphp
                                    @if ($postDescriptionText !== '')
                                        <div class="pf-prose line-clamp-3 mt-2 text-sm leading-relaxed text-(--pf-text-muted)">
                                            @if (\Illuminate\Support\Str::length($postDescriptionText) <= 1000)
                                                {!! $postDescriptionHtml !!}
                                            @else
                                                <p>{{ \Illuminate\Support\Str::limit($postDescriptionText, 1000) }}</p>
                                            @endif
                                        </div>
                                    @endif
                                @endif

                                <div class="mt-auto flex items-center justify-between gap-3 pt-5 text-[11px] text-(--pf-text-muted)">
                                    <span class="inline-flex items-center gap-1.5">
                                        @if ($post->user?->name)
                                            <span class="pf-mono truncate">{{ $post->user->name }}</span>
                                        @elseif ($post->reading_time)
                                            <span>{{ __(':min min read', ['min' => $post->reading_time]) }}</span>
                                        @endif
                                    </span>
                                    <span class="pf-mono inline-flex shrink-0 items-center gap-2.5">
                                        @if ($post->reading_time && $post->user?->name)
                                            <span>{{ __(':min min read', ['min' => $post->reading_time]) }}</span>
                                        @endif
                                        @if ($post->views)
                                            <span class="inline-flex items-center gap-1" title="{{ __('Views') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                {{ $post->views }}
                                            </span>
                                        @endif
                                        {{-- A span, not a second link: the title's
                                             ::after already covers this corner, so a
                                             link here would duplicate the post in the
                                             tab order. It reads as clickable because
                                             clicking it now opens the post. --}}
                                        <span class="font-semibold text-(--pf-primary) transition group-hover:underline">
                                            {{ __('Read more') }} &rarr;
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </article>
                    @empty
                        {{-- Reachable: a portfolio site can have the blog feature on and
                             no published posts yet, and the nav may still link here. A
                             dashed empty row looks like a bug; an invitation back to
                             the work does not. --}}
                        <div class="sm:col-span-2 lg:col-span-3">
                            <div class="pf-card flex flex-col items-center px-6 py-20 text-center">
                                <span class="pf-service-icon mb-5" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                    </svg>
                                </span>
                                <h2 class="pf-heading text-lg font-bold">{{ __('Nothing published yet') }}</h2>
                                <p class="mt-2 max-w-sm text-sm text-(--pf-text-muted)">
                                    {{ __('Notes from the work will show up here as they are written.') }}
                                </p>
                                <a href="{{ route('home') }}" class="pf-btn pf-btn-primary mt-6">
                                    {{ __('Back to the work') }} <span class="pf-btn-arrow">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Only when there is more than one page of them: a pager on a
                     single page of results is a control for nothing.

                     Hand-built rather than ->links(): Laravel's default markup is
                     unstyled blue underlined text, and the ecommerce and default
                     themes both ship their own. This is the portfolio's, so the
                     pager looks like the rest of the page instead of like a
                     framework leaked into it. The category filter is carried
                     across by the controller's withQueryString, so paging does
                     not silently drop a reader out of the view they chose. --}}
                @if ($posts->hasPages())
                    @php
                        $currentPage = $posts->currentPage();
                        $lastPage = $posts->lastPage();
                        // A short window around the current page, always including
                        // the first and last so the ends of the archive stay one
                        // click away instead of only reachable by counting.
                        $from = max(1, min($currentPage - 2, $lastPage - 4));
                        $to = min($lastPage, max($from + 4, $currentPage + 2));
                    @endphp
                    <nav aria-label="{{ __('Blog pagination') }}" class="mt-14 flex items-center justify-center gap-2">
                        @if ($currentPage > 1)
                            <a href="{{ $posts->previousPageUrl() }}" rel="prev"
                                class="pf-btn px-4! py-2! text-xs!" aria-label="{{ __('Previous page') }}">
                                <span class="pf-btn-arrow rotate-180">&rarr;</span> {{ __('Previous') }}
                            </a>
                        @endif

                        @if ($from > 1)
                            <a href="{{ $posts->url(1) }}" class="pf-btn min-w-9! justify-center px-3! py-2! text-xs!"
                                aria-label="{{ __('Go to page 1') }}">1</a>
                            @if ($from > 2)
                                <span class="pf-mono px-1 text-xs text-(--pf-text-muted)" aria-hidden="true">&hellip;</span>
                            @endif
                        @endif

                        @for ($page = $from; $page <= $to; $page++)
                            @if ($page === $currentPage)
                                <span class="pf-btn pf-btn-primary min-w-9! justify-center px-3! py-2! text-xs!"
                                    aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $posts->url($page) }}" class="pf-btn min-w-9! justify-center px-3! py-2! text-xs!"
                                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($to < $lastPage)
                            @if ($to < $lastPage - 1)
                                <span class="pf-mono px-1 text-xs text-(--pf-text-muted)" aria-hidden="true">&hellip;</span>
                            @endif
                            <a href="{{ $posts->url($lastPage) }}" class="pf-btn min-w-9! justify-center px-3! py-2! text-xs!"
                                aria-label="{{ __('Go to page :page', ['page' => $lastPage]) }}">{{ $lastPage }}</a>
                        @endif

                        @if ($currentPage < $lastPage)
                            <a href="{{ $posts->nextPageUrl() }}" rel="next"
                                class="pf-btn px-4! py-2! text-xs!" aria-label="{{ __('Next page') }}">
                                {{ __('Next') }} <span class="pf-btn-arrow">&rarr;</span>
                            </a>
                        @endif
                    </nav>
                @endif
            </div>
        </section>
    </main>

    @include('frontend.themes.portfolio.partials.footer')

    <livewire:frontend.chat-widget />

    @fluxScripts
    <script src="{{ asset('themes/portfolio/script.js') }}" defer></script>
@include('partials.custom-code-body')
</body>
</html>
