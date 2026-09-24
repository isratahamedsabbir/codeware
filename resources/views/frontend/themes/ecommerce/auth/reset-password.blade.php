@php
    $title = __('Reset password');
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
<body class="bg-page-bg font-storefront text-zinc-800 antialiased">

@include('frontend.themes.ecommerce.partials.header')

<main>
    <div class="mx-auto flex max-w-7xl justify-center px-4 py-10 sm:px-6 md:py-14">
        <div class="w-full max-w-md">
            <div class="rounded-card border border-zinc-200 bg-white p-6 sm:p-8">
                <h1 class="text-xl font-bold uppercase tracking-wide text-zinc-800">{{ __('Reset password') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Choose a new password for your account.') }}</p>

                @if (session('status'))
                    <div class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="mt-6 flex flex-col gap-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ request()->route('token') }}">

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-zinc-700">
                            {{ __('Email') }}
                        </label>
                        <input id="email" name="email" value="{{ request('email') }}" type="email" required
                            autocomplete="email"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800 outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
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
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800 outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
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
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800 outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
                        @error('password_confirmation')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" data-test="reset-password-button"
                        class="rounded-full bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                        {{ __('Reset password') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
</body>
</html>