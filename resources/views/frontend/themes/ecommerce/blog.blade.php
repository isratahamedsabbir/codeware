<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    @include('partials.custom-code-head')
</head>
<body class="bg-white font-storefront text-sf-text antialiased">

@include('frontend.themes.ecommerce.partials.header')

@php
    $activeCategory = (string) request()->query('category', '');
    $crumbs = [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Blog'), 'url' => null],
    ];
@endphp

@include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <header class="mb-8 text-center">
        <h1 class="text-3xl font-extrabold text-sf-heading sm:text-4xl">{{ __('Our Blog') }}</h1>
        <p class="mx-auto mt-2 max-w-xl text-sm text-zinc-500">{{ __('Guides, news and updates from the team.') }}</p>
    </header>

    @if ($categories->isNotEmpty())
        <nav aria-label="{{ __('Blog categories') }}" class="mb-8 flex flex-wrap items-center justify-center gap-2">
            <a href="{{ route('blog') }}"
                class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $activeCategory === '' ? 'bg-brand text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-brand/10 hover:text-brand' }}">
                {{ __('All') }}
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('blog', ['category' => $category->slug]) }}"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $activeCategory === $category->slug ? 'bg-brand text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-brand/10 hover:text-brand' }}">
                    {{ $category->name }}
                    <span class="ml-1 text-xs font-normal opacity-70">{{ $category->posts_count }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($posts->isNotEmpty())
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <article class="group flex flex-col overflow-hidden rounded-card border border-zinc-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <a href="{{ route('blog.post', $post->slug) }}" class="flex flex-1 flex-col">
                        <div class="relative aspect-[16/10] overflow-hidden bg-zinc-100">
                            @if ($post->featured_image)
                                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <div class="flex h-full items-center justify-center bg-brand/5 text-brand">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18.75" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                @if ($post->category?->page)
                                    <span class="text-xs font-semibold uppercase tracking-wide text-brand">{{ $post->category->name }}</span>
                                @endif
                                @if ($post->published_at)
                                    <span class="text-xs text-zinc-400">{{ $post->published_at->toDisplay() }}</span>
                                @endif
                            </div>
                            <h2 class="text-lg font-bold text-sf-heading group-hover:text-brand">{{ $post->title }}</h2>
                            @if (filled($post->description))
                                @php
                                    $postDescriptionHtml = (string) (is_array($post->description) ? ($post->description[app()->getLocale()] ?? reset($post->description)) : $post->description);
                                    $postDescriptionText = trim(html_entity_decode(strip_tags($postDescriptionHtml)));
                                @endphp
                                @if ($postDescriptionText !== '')
                                    <div class="rich-text mt-2 text-sm leading-relaxed text-zinc-500">
                                        @if (\Illuminate\Support\Str::length($postDescriptionText) <= 1000)
                                            {!! $postDescriptionHtml !!}
                                        @else
                                            <p>{{ \Illuminate\Support\Str::limit($postDescriptionText, 1000) }}</p>
                                        @endif
                                    </div>
                                @endif
                            @endif
                            @if ($post->user?->name)
                                <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-zinc-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" />
                                    </svg>
                                    {{ $post->user->name }}
                                </p>
                            @endif
                            <div class="mt-auto flex items-center justify-between pt-4 text-xs text-zinc-400">
                                <span>{{ __(':min min read', ['min' => $post->reading_time]) }}</span>
                                <span class="inline-flex items-center gap-3">
                                    <span class="inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        {{ $post->views }}
                                    </span>
                                    <span class="font-semibold text-brand group-hover:underline">{{ __('Read more') }} →</span>
                                </span>
                            </div>
                        </div>
                    </a>
                </article>
            @endforeach
        </div>
        <div class="mt-10">
            {{ $posts->links() }}
        </div>
    @else
        <div class="flex flex-col items-center justify-center rounded-card border border-dashed border-zinc-200 py-24 text-center">
            <h2 class="text-lg font-semibold text-sf-heading">{{ __('No posts published yet') }}</h2>
            <a href="{{ route('shop') }}" class="mt-5 rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                {{ __('Continue shopping') }}
            </a>
        </div>
    @endif
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>