<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-zinc-50 font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

<section class="bg-gradient-to-br from-brand to-brand/80 text-white">
    <div class="mx-auto max-w-5xl px-4 py-14 text-center sm:px-6 sm:py-20">
        <span class="inline-block rounded-full bg-white/15 px-4 py-1 text-xs font-semibold uppercase tracking-widest">{{ __('We are hiring') }}</span>
        <h1 class="mt-4 text-3xl font-extrabold sm:text-5xl">{{ __('Build your career with us') }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-base text-white/80">{{ __('Join a team that values curiosity, ownership and growth. Explore our open positions and apply in minutes.') }}</p>
        <p class="mt-6 text-sm font-semibold text-white/90">{{ $totalOpen === 0 ? __('No open positions') : $totalOpen.' '.__('open positions') }}</p>
    </div>
</section>

<main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
    @if ($departments->count() > 1)
        <nav aria-label="{{ __('Departments') }}" class="mb-8 flex flex-wrap items-center justify-center gap-2">
            <a href="{{ route('careers') }}"
                class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $department === '' ? 'bg-brand text-white shadow-sm' : 'bg-white text-zinc-600 ring-1 ring-zinc-200 hover:text-brand hover:ring-brand/40' }}">{{ __('All') }}</a>
            @foreach ($departments as $name)
                <a href="{{ route('careers', ['department' => $name]) }}"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $department === $name ? 'bg-brand text-white shadow-sm' : 'bg-white text-zinc-600 ring-1 ring-zinc-200 hover:text-brand hover:ring-brand/40' }}">{{ $name }}</a>
            @endforeach
        </nav>
    @endif

    <div class="space-y-4">
        @forelse ($jobs as $job)
            <a href="{{ route('careers.show', $job->slug) }}"
                class="group flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand/40 hover:shadow-lg sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    @if ($job->department)
                        <span class="text-xs font-bold uppercase tracking-wider text-brand">{{ $job->department }}</span>
                    @endif
                    <h2 class="mt-1 text-xl font-bold text-sf-heading transition group-hover:text-brand">{{ $job->title }}</h2>
                    <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-zinc-500">
                        @if ($job->location)
                            <li class="inline-flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                {{ $job->location }}
                            </li>
                        @endif
                        <li class="inline-flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            {{ $job->employmentTypeLabel() }}
                        </li>
                        <li class="inline-flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                            {{ $job->vacancy }} {{ $job->vacancy === 1 ? __('vacancy') : __('vacancies') }}
                        </li>
                        @if ($job->deadline)
                            <li class="inline-flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                                {{ __('Apply by') }} {{ $job->deadline->toFormattedDateString() }}
                            </li>
                        @endif
                    </ul>
                </div>
                <span class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white transition group-hover:gap-3 group-hover:shadow-md">
                    {{ __('View & apply') }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center">
                <p class="font-semibold text-sf-heading">{{ __('No open positions right now') }}</p>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Please check back soon — new roles are posted regularly.') }}</p>
            </div>
        @endforelse
    </div>
</main>

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
