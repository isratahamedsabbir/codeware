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
        // The header and footer partials are shared with the one-pager and both
        // read these, so they are resolved here rather than in each partial.
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');
        $socials = \App\Support\PortfolioSocials::all();
    @endphp

    @include('frontend.themes.portfolio.partials.header')

    <main>
        <section class="px-6 pt-32 pb-16">
            <div class="mx-auto max-w-6xl">
                {{-- A back link rather than a breadcrumb trail: on this theme the
                     one-pager is the only other page, so "← Back" says where you
                     actually came from. --}}
                <a href="{{ route('home') }}" class="pf-mono text-xs text-(--pf-text-muted) transition hover:text-(--pf-primary)">
                    &larr; {{ __('Back') }}
                </a>

                <div class="mt-8 max-w-2xl">
                    <span class="pf-eyebrow pf-mono">{{ __('Writing') }}</span>
                    <h1 class="pf-heading mt-5 text-4xl font-bold sm:text-5xl">{{ __('Notes from the work') }}</h1>
                    <p class="mt-4 text-(--pf-text-muted)">{{ __('Things worth writing down while building them.') }}</p>
                </div>

                {{-- Categories only when there are any. The filter row with one
                     entry in it is a control that navigates nowhere. --}}
                @if ($categories->isNotEmpty())
                    <nav aria-label="{{ __('Blog categories') }}" class="mt-12 flex flex-wrap items-center gap-2">
                        <a href="{{ route('blog') }}"
                            class="rounded-full border px-3.5 py-1.5 text-xs font-medium transition {{ request()->query('category') ? 'border-(--pf-border) text-(--pf-text-muted) hover:text-(--pf-primary)' : 'border-(--pf-primary) text-(--pf-primary)' }}">
                            {{ __('All') }}
                        </a>
                        @foreach ($categories as $category)
                            {{-- A post's category slug, like the post's own, lives on
                                 the paired Page (the controller filters on
                                 category.page.slug), so this is not $category->slug. --}}
                            <a href="{{ route('blog', ['category' => $category->page->slug]) }}"
                                class="rounded-full border px-3.5 py-1.5 text-xs font-medium transition {{ request()->query('category') === $category->page->slug ? 'border-(--pf-primary) text-(--pf-primary)' : 'border-(--pf-border) text-(--pf-text-muted) hover:text-(--pf-primary)' }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif
            </div>
        </section>

        <section class="border-t border-(--pf-border) px-6 py-16">
            <div class="mx-auto max-w-6xl">
                @forelse ($posts as $post)
                    <article data-pf-reveal
                        class="pf-card pf-card-hover group border-b border-(--pf-border) py-8 first:pt-0 last:border-b-0">
                        <div class="grid gap-6 sm:grid-cols-[1fr_auto] sm:items-start">
                            <div class="min-w-0">
                                @if ($post->category?->page && $post->category->status === 'active')
                                    <a href="{{ route('blog', ['category' => $post->category->page->slug]) }}"
                                        class="pf-mono text-[11px] text-(--pf-primary) hover:underline">
                                        {{ $post->category->name }}
                                    </a>
                                @endif

                                <h2 class="pf-heading mt-2 text-2xl font-bold">
                                    <a href="{{ route('blog.post', $post->slug) }}" class="group-hover:text-(--pf-primary)">
                                        {{ $post->title }}
                                    </a>
                                </h2>

                                <p class="pf-mono mt-3 text-[11px] text-(--pf-text-muted)">
                                    @if ($post->published_at)
                                        <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->toDisplay() }}</time>
                                    @endif
                                    @if ($post->reading_time)
                                        <span class="px-1.5">&middot;</span>{{ __(':min min read', ['min' => $post->reading_time]) }}
                                    @endif
                                </p>

                                @if (filled($post->description))
                                    <div class="pf-prose mt-4 line-clamp-3 text-sm leading-relaxed text-(--pf-text-muted)">
                                        {!! $post->description !!}
                                    </div>
                                @endif
                            </div>

                            @if (filled($post->featured_image))
                                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}"
                                    class="h-32 w-full rounded-xl object-cover sm:h-24 sm:w-40" loading="lazy" decoding="async">
                            @endif
                        </div>
                    </article>
                @empty
                    {{-- Reachable: a portfolio site can have the blog feature on and
                         no published posts yet, and the nav may still link here. --}}
                    <p class="py-16 text-center text-(--pf-text-muted)">{{ __('Nothing published yet.') }}</p>
                @endforelse

                {{-- Only when there is more than one page of them: a pager on a
                     single page of results is a control for nothing. --}}
                @if ($posts->hasPages())
                    <div class="mt-12">
                        {{ $posts->links() }}
                    </div>
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
