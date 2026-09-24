@php
    $title = __('Create an account');
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
                <h1 class="text-xl font-bold uppercase tracking-wide text-sf-text">{{ __('Create an account') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Enter your details below to start shopping with us.') }}</p>

                @if (session('status'))
                    <div class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="mt-6 flex flex-col gap-5">
                    @csrf

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-semibold text-zinc-700">
                            {{ __('Name') }}
                        </label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                            autocomplete="name" placeholder="{{ __('Full name') }}"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-zinc-700">
                            {{ __('Email address') }}
                        </label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            autocomplete="email" placeholder="email@example.com"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-zinc-700">
                            {{ __('Password') }}
                        </label>
                        <input id="password" name="password" type="password" required
                            autocomplete="new-password" placeholder="{{ __('Password') }}"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-zinc-700">
                            {{ __('Confirm password') }}
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            autocomplete="new-password" placeholder="{{ __('Confirm password') }}"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
                    </div>

                    <button type="submit" data-test="register-user-button"
                        class="rounded-full bg-sf-button px-4 py-2.5 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
                        {{ __('Create account') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    {{ __('Already have an account?') }}
                    <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">
                        {{ __('Sign in') }}
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