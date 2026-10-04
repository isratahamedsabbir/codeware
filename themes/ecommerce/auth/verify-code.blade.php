@php
    $title = __('Enter your code');
    $page = null;
    $navPages = \App\Models\Page::ofType('page')->published()->orderBy('sort_order')->get();
    $menuItems = \App\Models\MenuItem::where('group', 'frontend')->where('is_active', true)->orderBy('sort_order')->get();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

<main>
    <div class="mx-auto flex max-w-7xl justify-center px-4 py-10 sm:px-6 md:py-14">
        <div class="w-full max-w-md">
            <div class="rounded-card border border-zinc-200 bg-white p-6 sm:p-8">
                <h1 class="text-xl font-bold uppercase tracking-wide text-sf-text">{{ __('Enter your code') }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('We sent a six-digit code to :email. Enter it below to choose a new password.', ['email' => $email]) }}
                </p>

                <form method="POST" action="{{ route('password.verify.check') }}" class="mt-6 flex flex-col gap-5">
                    @csrf

                    <div>
                        <label for="code" class="mb-1.5 block text-sm font-semibold text-zinc-700">
                            {{ __('Verification code') }}
                        </label>
                        <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                            required autofocus autocomplete="one-time-code" placeholder="000000"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-center text-lg font-semibold tracking-[0.5em] text-sf-text outline-none transition placeholder:text-zinc-300 focus:border-brand focus:ring-2 focus:ring-brand/20">
                        @error('code')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" data-test="verify-code-button"
                        class="rounded-full bg-sf-button px-4 py-2.5 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
                        {{ __('Continue') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    {{ __('Wrong address, or no code yet?') }}
                    <a href="{{ route('password.request') }}" class="font-semibold text-brand hover:underline">
                        {{ __('Send it again') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</main>

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
