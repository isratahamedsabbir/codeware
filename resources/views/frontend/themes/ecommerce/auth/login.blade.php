@php
    $title = __('Sign in');
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
                <h1 class="text-xl font-bold uppercase tracking-wide text-zinc-800">{{ __('Sign in') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Log in to see your orders and manage your account.') }}</p>

                @if (session('status'))
                    <div class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-6 flex flex-col gap-5" data-login-form>
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-zinc-700">
                            {{ __('Email address') }}
                        </label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                            autocomplete="email" placeholder="email@example.com"
                            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-800 outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="password" class="block text-sm font-semibold text-zinc-700">
                                {{ __('Password') }}
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand hover:underline">
                                    {{ __('Forgot your password?') }}
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <input id="password" name="password" type="password" required autocomplete="current-password"
                                placeholder="{{ __('Password') }}"
                                class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 pr-10 text-sm text-zinc-800 outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20">
                            <button type="button" data-toggle-password
                                class="absolute inset-y-0 end-0 flex items-center px-3 text-zinc-400 transition-colors hover:text-zinc-600"
                                aria-label="{{ __('Toggle password visibility') }}">
                                <svg data-icon-eye xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg data-icon-eye-slash xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if (\App\Support\Recaptcha::enabled())
                        <div>
                            <input type="hidden" name="g-recaptcha-response" data-recaptcha-response>
                            @error('g-recaptcha-response')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input id="remember" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-2 border-zinc-300 bg-white checked:bg-brand checked:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
                        <span class="text-sm font-normal text-zinc-700">{{ __('Remember me') }}</span>
                    </label>

                    <button type="submit" data-test="login-button"
                        class="rounded-full bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                        {{ __('Sign in') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    {{ __("Don't have an account?") }}
                    <a href="{{ route('register') }}" class="font-semibold text-brand hover:underline">
                        {{ __('Create one') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@if (\App\Support\Recaptcha::enabled())
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
    <script>
        document.querySelector('[data-login-form]').addEventListener('submit', function (e) {
            e.preventDefault();
            const form = e.target;
            grecaptcha.ready(function () {
                grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', { action: 'login' }).then(function (token) {
                    form.querySelector('[data-recaptcha-response]').value = token;
                    form.submit();
                });
            });
        });
    </script>
@endif

<script>
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById('password');
            const iconEye = button.querySelector('[data-icon-eye]');
            const iconEyeSlash = button.querySelector('[data-icon-eye-slash]');
            if (input.type === 'password') {
                input.type = 'text';
                iconEye.classList.add('hidden');
                iconEyeSlash.classList.remove('hidden');
            } else {
                input.type = 'password';
                iconEye.classList.remove('hidden');
                iconEyeSlash.classList.add('hidden');
            }
        });
    });
</script>

@fluxScripts
</body>
</html>