@php
    $socialProviders = collect(\App\Support\SocialAccount::PROVIDERS)
        ->filter(fn ($p) => \App\Support\SocialAccount::configured($p));
@endphp

@if ($socialProviders->isNotEmpty())
    <div class="mt-6 flex items-center gap-3 text-xs uppercase tracking-wide text-zinc-400">
        <span class="h-px flex-1 bg-zinc-200"></span>
        {{ __('or continue with') }}
        <span class="h-px flex-1 bg-zinc-200"></span>
    </div>

    <div class="mt-4 grid gap-3 {{ $socialProviders->count() > 1 ? 'sm:grid-cols-2' : '' }}">
        @if ($socialProviders->contains('google'))
            <a href="{{ route('social.redirect', 'google') }}"
                class="flex items-center justify-center gap-2 rounded-full border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50">
                <svg class="h-4 w-4" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8c4.4-4.1 7.1-10.1 7.1-17.5z"/>
                    <path fill="#FBBC05" d="M10.5 28.7c-.5-1.4-.8-3-.8-4.7s.3-3.2.8-4.7l-7.9-6.1C.9 16.4 0 20.1 0 24s.9 7.6 2.6 10.8l7.9-6.1z"/>
                    <path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.5-5.8c-2.1 1.4-4.8 2.3-8.4 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/>
                </svg>
                Google
            </a>
        @endif

        @if ($socialProviders->contains('facebook'))
            <a href="{{ route('social.redirect', 'facebook') }}"
                class="flex items-center justify-center gap-2 rounded-full border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#1877F2" d="M24 12a12 12 0 1 0-13.9 11.9v-8.4H7.1V12h3V9.4c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9V12h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12z"/>
                </svg>
                Facebook
            </a>
        @endif
    </div>
@endif
