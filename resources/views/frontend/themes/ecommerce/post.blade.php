<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
</head>
<body class="bg-white text-zinc-800 antialiased">

@include('frontend.themes.ecommerce.partials.header')

@php
    $crumbs = [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Blog'), 'url' => route('blog')],
        ['label' => $post->title, 'url' => null],
    ];
@endphp

@include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

<main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
    <article>
        @if ($post->category?->page && $post->category->status === 'active')
            <a href="{{ route('blog', ['category' => $post->category->slug]) }}"
                class="text-xs font-semibold uppercase tracking-wide text-primary hover:underline">
                {{ $post->category->name }}
            </a>
        @endif

        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl">{{ $post->title }}</h1>

        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-500">
            @if ($post->user?->name)
                <span class="inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" />
                    </svg>
                    {{ $post->user->name }}
                </span>
            @endif
            @if ($post->published_at)
                <span class="inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    {{ $post->published_at->toDisplay() }}
                </span>
            @endif
            <span class="inline-flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                {{ __(':min min read', ['min' => $post->reading_time]) }}
            </span>
        </div>

        @if ($post->featured_image)
            <div class="mt-8 overflow-hidden rounded-2xl border border-zinc-100 shadow-sm">
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="aspect-[16/9] w-full object-cover">
            </div>
        @endif

        @if (filled($post->description))
            <div class="mt-8 whitespace-pre-line border-b border-zinc-100 pb-8 text-lg leading-relaxed text-zinc-700">
                {{ $post->description }}
            </div>
        @endif

        @if ($post->tags->isNotEmpty())
            <div class="mt-8 flex flex-wrap items-center gap-2">
                @foreach ($post->tags as $tag)
                    <a href="{{ route('shop.tag', $tag->slug) }}"
                        class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 transition hover:bg-primary/10 hover:text-primary">
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </article>

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="border-t border-zinc-100 py-12">
            <h2 class="mb-6 text-2xl font-bold text-zinc-900">{{ $section->name }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($section->localizedCards() as $card)
                    <div class="group overflow-hidden rounded-2xl border border-zinc-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        @if ($card['image'])
                            <div class="aspect-[16/10] overflow-hidden bg-zinc-100">
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

    @if ($related->isNotEmpty())
        <section class="border-t border-zinc-100 py-12">
            <h2 class="mb-6 text-2xl font-bold text-zinc-900">{{ __('Related posts') }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $relatedPost)
                    <a href="{{ route('blog.post', $relatedPost->slug) }}"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-zinc-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        @if ($relatedPost->featured_image)
                            <div class="relative aspect-[16/10] overflow-hidden bg-zinc-100">
                                <img src="{{ $relatedPost->featured_image }}" alt="{{ $relatedPost->title }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col p-4">
                            <h3 class="line-clamp-2 font-semibold text-zinc-900 group-hover:text-primary">{{ $relatedPost->title }}</h3>
                            @if ($relatedPost->published_at)
                                <span class="mt-2 text-xs text-zinc-400">{{ $relatedPost->published_at->toDisplay() }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
</body>
</html>