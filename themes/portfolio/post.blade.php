<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    <link rel="preload" href="{{ asset('fonts/instrument-sans/instrument-sans-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('themes/portfolio/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/portfolio/css/style.css') }}">
    <style>[x-cloak]{display:none!important}</style>
    @include('partials._custom-code-head')
</head>
<body class="theme-portfolio antialiased">

    @php
        // Read by the shared header and footer partials - see the one-pager.
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');
        $socials = \App\Support\PortfolioSocials::all();
    @endphp

    @include('theme-portfolio::partials._header')

    <main>
        <article class="px-6 pt-32 pb-16">
            <div class="mx-auto max-w-3xl">
                <a href="{{ route('blog') }}" class="pf-mono text-xs text-(--pf-text-muted) transition hover:text-(--pf-primary)">
                    &larr; {{ __('All posts') }}
                </a>

                <header class="mt-8">
                    @if ($post->category?->page && $post->category->status === 'active')
                        <a href="{{ route('blog', ['category' => $post->category->page->slug]) }}"
                            class="pf-mono text-[11px] text-(--pf-primary) hover:underline">
                            {{ $post->category->name }}
                        </a>
                    @endif

                    <h1 class="pf-heading mt-3 text-3xl font-bold sm:text-4xl">{{ $post->title }}</h1>

                    <p class="pf-mono mt-4 flex flex-wrap items-center gap-x-3 text-[11px] text-(--pf-text-muted)">
                        @if ($post->user?->name)
                            <span>{{ $post->user->name }}</span>
                        @endif
                        @if ($post->published_at)
                            <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->toDisplay() }}</time>
                        @endif
                        @if ($post->reading_time)
                            <span>{{ __(':min min read', ['min' => $post->reading_time]) }}</span>
                        @endif
                    </p>
                </header>

                @if (filled($post->featured_image))
                    {{-- The lead image sits just under the header, so on a phone it is
                         very often this page's largest contentful paint. It wants
                         eager + high priority rather than the lazy loading used
                         for the cards further down. --}}
                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" fetchpriority="high" width="1200" height="675"
                        class="mt-10 aspect-[16/9] w-full rounded-2xl object-cover">
                @endif


                {{-- The post body, as stored: description is authored rich text and
                     is the post's own content, so it is emitted unescaped the same
                     way the ecommerce theme's post page emits it. Everything a
                     visitor can type into it is escaped by the admin editor that
                     produced it, not here. --}}
                @if (filled($post->description))
                    <div class="pf-prose mt-10 text-base leading-relaxed text-(--pf-text)">
                        {!! $post->description !!}
                    </div>
                @endif

                @if ($post->tags->isNotEmpty())
                    <div class="mt-10 flex flex-wrap items-center gap-2">
                        @foreach ($post->tags as $tag)
                            {{-- No /blog/tag route in this theme (see
                                 themes/portfolio/routes/web.php), and the ecommerce one is
                                 404-guarded away here, so tags render as plain
                                 labels rather than as links that would dead-end. --}}
                            <span class="pf-mono rounded-full bg-(--pf-bg-inset) px-3 py-1 text-[11px] text-(--pf-text-muted)">
                                #{{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </article>

        {{-- The post's own Puck-built CMS sections, same data the ecommerce post
             page renders. A post with none is normal, not an error. --}}
        @foreach ($sections as $section)
            @continue(blank($section->localizedCards()))

            <section class="border-t border-(--pf-border) px-6 py-16">
                <div class="mx-auto max-w-6xl">
                    <h2 class="pf-heading text-2xl font-bold">{{ $section->name }}</h2>
                    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($section->localizedCards() as $card)
                            <div class="pf-card pf-card-hover overflow-hidden rounded-2xl">
                                @if ($card['image'])
                                    <img src="{{ $card['image'] }}" alt="{{ $card['title'] ?? '' }}" width="800" height="500"
                                        class="aspect-[16/10] w-full object-cover" loading="lazy" decoding="async">
                                @endif
                                <div class="p-5">
                                    @if ($card['title'])
                                        <h3 class="pf-heading font-semibold">{{ $card['title'] }}</h3>
                                    @endif
                                    @if ($card['description'])
                                        <p class="mt-2 text-sm leading-relaxed text-(--pf-text-muted)">{{ $card['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endforeach

        @if (\App\Support\Features::enabled('comments'))
            <div class="border-t border-(--pf-border) px-6 py-16">
                <div class="mx-auto max-w-3xl">
                    <livewire:frontend.blog-comments :post-id="$post->id" :key="'post-comments-'.$post->id" />
                </div>
            </div>
        @endif

        @if ($related->isNotEmpty())
            <section class="border-t border-(--pf-border) bg-(--pf-bg-elevated)/40 px-6 py-16">
                <div class="mx-auto max-w-6xl">
                    <h2 class="pf-heading text-2xl font-bold">{{ __('Related posts') }}</h2>

                    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($related as $relatedPost)
                            <a href="{{ route('blog.post', $relatedPost->slug) }}"
                                class="pf-card pf-card-hover pf-card-rail group flex flex-col rounded-2xl p-6">
                                @if (filled($relatedPost->featured_image))
                                    <img src="{{ $relatedPost->featured_image }}" alt="{{ $relatedPost->title }}" width="400" height="160"
                                        class="mb-5 h-40 w-full rounded-xl object-cover" loading="lazy" decoding="async">
                                @endif

                                <h3 class="pf-heading font-semibold group-hover:text-(--pf-primary)">
                                    {{ $relatedPost->title }}
                                </h3>

                                @if ($relatedPost->published_at)
                                    <p class="pf-mono mt-3 text-[11px] text-(--pf-text-muted)">
                                        {{ $relatedPost->published_at->toDisplay() }}
                                    </p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>

    @include('theme-portfolio::partials._footer')

    @include('frontend.partials._chat-widget')
    <script src="{{ asset('themes/portfolio/js/script.js') }}" defer></script>
@include('partials._custom-code-body')
</body>
</html>
