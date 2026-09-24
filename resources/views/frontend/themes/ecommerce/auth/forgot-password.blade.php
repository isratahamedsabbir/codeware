@php
    $title = __('Forgot password');
    $page = null;
    $navPages = \App\Models\Page::ofType('page')->published()->orderBy('sort_order')->get();
    $menuItems = \App\Models\MenuItem::where('group', 'frontend')->where('is_active', true)->orderBy('sort_order')->get();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('frontend.themes.ecommerce.partials.header')

<main>
    <div class="mx-auto flex max-w-7xl justify-center px-4 py-10 sm:px-6 md:py-14">
        <div class="w-full max-w-md">
            <div class="rounded-card border border-zinc-200 bg-white p-6 sm:p-8">
                <h1 class="text-xl font-bold uppercase tracking-wide text-sf-text">{{ __('Forgot password') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Enter your email to receive a password reset link.') }}</p>

                @if (session('status'))
                    <div class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="mt-6 flex flex-col gap-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-zinc-700">
                            {{ __('Email address') }}
                        </label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                            autocomplete="email" placeholder="email@example.com"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" data-test="email-password-reset-link-button"
                        class="rounded-full bg-sf-button px-4 py-2.5 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
                        {{ __('Email password reset link') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    {{ __('Or, return to') }}
                    <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">
                        {{ __('log in') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
</body>
</html>