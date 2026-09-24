<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
</head>
<body class="bg-white text-zinc-800 antialiased">

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
        <h1 class="text-3xl font-extrabold text-zinc-900 sm:text-4xl">{{ __('Our Blog') }}</h1>
        <p class="mx-auto mt-2 max-w-xl text-sm text-zinc-500">{{ __('Guides, news and updates from the team.') }}</p>
    </header>

    @if ($categories->isNotEmpty())
        <nav aria-label="{{ __('Blog categories') }}" class="mb-8 flex flex-wrap items-center justify-center gap-2">
            <a href="{{ route('blog') }}"
                class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $activeCategory === '' ? 'bg-primary text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-primary/10 hover:text-primary' }}">
                {{ __('All') }}
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('blog', ['category' => $category->slug]) }}"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $activeCategory === $category->slug ? 'bg-primary text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-primary/10 hover:text-primary' }}">
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
                                    <span class="text-xs font-semibold uppercase tracking-wide text-primary">{{ $post->category->name }}</span>
                                @endif
                                @if ($post->published_at)
                                    <span class="text-xs text-zinc-400">{{ $post->published_at->toDisplay() }}</span>
                                @endif
                            </div>
                            <h2 class="text-lg font-bold text-zinc-900 group-hover:text-primary">{{ $post->title }}</h2>
                            @if (filled($post->description))
                                <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-zinc-500">{{ $post->description }}</p>
                            @endif
                            <div class="mt-auto flex items-center justify-between pt-4 text-xs text-zinc-400">
                                <span>{{ __(':min min read', ['min' => $post->reading_time]) }}</span>
                                <span class="font-semibold text-primary group-hover:underline">{{ __('Read more') }} →</span>
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
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('No posts published yet') }}</h2>
            <a href="{{ route('shop') }}" class="mt-5 rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                {{ __('Continue shopping') }}
            </a>
        </div>
    @endif
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
</body>
</html>