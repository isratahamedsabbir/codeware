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
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
        <a href="{{ route('careers') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-white/80 hover:text-white">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            {{ __('All openings') }}
        </a>
        @if ($job->department)
            <p class="mt-5 text-xs font-bold uppercase tracking-widest text-white/70">{{ $job->department }}</p>
        @endif
        <h1 class="mt-1 text-3xl font-extrabold sm:text-4xl">{{ $job->title }}</h1>
        <div class="mt-4 flex flex-wrap gap-2 text-sm">
            @if ($job->location)
                <span class="rounded-full bg-white/15 px-3 py-1">{{ $job->location }}</span>
            @endif
            <span class="rounded-full bg-white/15 px-3 py-1">{{ $job->employmentTypeLabel() }}</span>
            <span class="rounded-full bg-white/15 px-3 py-1">{{ $job->vacancy }} {{ $job->vacancy === 1 ? __('vacancy') : __('vacancies') }}</span>
        </div>
    </div>
</section>

<main class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        @if ($job->description)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-bold text-sf-heading">{{ __('About the role') }}</h2>
                <div class="mt-3 whitespace-pre-line text-[15px] leading-7 text-zinc-600">{{ $job->description }}</div>
            </section>
        @endif
        @if ($job->requirements)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-bold text-sf-heading">{{ __('Requirements') }}</h2>
                <div class="mt-3 whitespace-pre-line text-[15px] leading-7 text-zinc-600">{{ $job->requirements }}</div>
            </section>
        @endif
        @unless ($job->description || $job->requirements)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 text-sm text-zinc-500 shadow-sm">
                {{ __('More details about this role will be shared during the hiring process.') }}
            </section>
        @endunless
    </div>

    <aside class="space-y-6 lg:col-span-1">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-sf-heading">{{ __('Job overview') }}</h2>
            <dl class="mt-4 divide-y divide-zinc-100 text-sm">
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-zinc-500">{{ __('Employment') }}</dt><dd class="font-semibold text-sf-heading">{{ $job->employmentTypeLabel() }}</dd></div>
                @if ($job->location)
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-zinc-500">{{ __('Location') }}</dt><dd class="text-right font-semibold text-sf-heading">{{ $job->location }}</dd></div>
                @endif
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-zinc-500">{{ __('Vacancies') }}</dt><dd class="font-semibold text-sf-heading">{{ $job->vacancy }}</dd></div>
                @if ($job->deadline)
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-zinc-500">{{ __('Apply by') }}</dt><dd class="font-semibold text-sf-heading">{{ $job->deadline->toFormattedDateString() }}</dd></div>
                @endif
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-zinc-500">{{ __('Posted') }}</dt><dd class="font-semibold text-sf-heading">{{ $job->created_at->toFormattedDateString() }}</dd></div>
            </dl>
        </div>

        <div id="apply" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm lg:sticky lg:top-24">
            <h2 class="text-base font-bold text-sf-heading">{{ __('Apply for this position') }}</h2>
            <p class="mb-5 mt-1 text-sm text-zinc-500">{{ __('Fill in your details and attach your résumé.') }}</p>
            @if ($job->isAcceptingApplications())
                <livewire:frontend.job-application-form :job-post-id="$job->id" :key="'job-apply-'.$job->id" />
            @else
                <div class="rounded-lg bg-zinc-100 px-4 py-3 text-sm text-zinc-600">{{ __('This position is no longer accepting applications.') }}</div>
            @endif
        </div>
    </aside>
</main>

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
