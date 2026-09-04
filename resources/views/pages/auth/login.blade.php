@php
    $loginPage = \App\Models\Page::where('slug', 'login')->first();
@endphp
<x-layouts::auth :title="$loginPage?->seo_title ?: __('Log in')" :description="$loginPage?->seo_description">
    <div class="flex flex-col gap-5 w-full">
        <x-auth-header
            :title="cms_constant('login', 'main_card', 'title') ?: __('Log in to your account')"
            :description="cms_constant('login', 'main_card', 'sub_title') ?: __('Enter your email and password below to log in')" />

        {{-- Session Status --}}
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            {{-- Email Address --}}
            <div class="flex flex-col gap-1.5"> 
                <label for="email" class="text-sm font-semibold text-white leading-tight">
                    {{ __('Email address') }}
                </label> 
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    autocomplete="email" placeholder="email@example.com"
                    class="
                        w-full rounded-md border border-gray-300 bg-white
                        px-3 py-2 text-sm font-normal text-gray-900
                        placeholder:text-gray-400
                        focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-gray-400
                        shadow-none
                        transition-colors duration-150
                    " />
                @error('email')
                    <p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}} 
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-sm font-semibold text-white leading-tight">
                    {{ __('Password') }}
                </label>
                <div class="relative">
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        placeholder="{{ __('Password') }}"
                        class="
                            w-full rounded-md border border-gray-300 bg-white
                            px-3 py-2 pr-10 text-sm font-normal text-gray-900
                            placeholder:text-gray-400
                            focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-gray-400
                            shadow-none
                            transition-colors duration-150
                        " />
                    <button type="button" id="toggle-password"
                        onclick="togglePassword()"
                        class="absolute inset-y-0 end-0 flex items-center px-3 text-gray-400 hover:text-gray-600 transition-colors duration-150 cursor-pointer"
                        aria-label="Toggle password visibility">
                        {{-- Eye icon (visible when password is hidden) --}}
                        <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        {{-- Eye-slash icon (visible when password is shown) --}}
                        <svg id="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.094-3.592M6.228 6.228A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.337 2.694M6.228 6.228L3 3m3.228 3.228l3.65 3.65M21 21l-3.228-3.228m0 0L14.35 14.35M17.772 17.772l-3.65-3.65" />
                        </svg>
                    </button> 
                    {{-- @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="absolute top-0 end-0 text-xs text-gray-500 hover:text-gray-800 transition-colors">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif --}}
                </div>
                @error('password')
                    <p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember Me --}}
            <div class="flex items-center gap-2.5">
                <input id="remember" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }}
                    class="
                        h-4 w-4 rounded border-2 border-gray-400
                        bg-white checked:bg-primary checked:border-primary
                        focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1
                        shadow-none appearance-none cursor-pointer
                        transition-colors duration-150
                        relative
                    "
                    style="
                        --tw-shadow: none;
                        box-shadow: none;
                    " />
                <label for="remember"
                    class="text-sm font-normal text-white cursor-pointer select-none leading-tight">
                    {{ __('Remember me') }}
                </label>
            </div> 

            {{-- Submit --}}
            <div class="pt-1"> 
                <button type="submit" data-test="login-button"
                    class="
                        w-full rounded-md bg-primary px-4 py-2.5
                        text-sm font-semibold text-white
                        hover:bg-secondary active:bg-secondary
                        focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2
                        shadow-none
                        transition-colors duration-150
                        cursor-pointer
                    ">
                    {{ __('Log in') }}
                </button>
            </div> 
        </form>

        @if (Route::has('register'))
            <p class="text-sm text-center text-gray-500">
                <!-- {{ __("Don't have an account?") }} -->
                {{-- <a href="{{ route('register') }}" class="font-semibold text-gray-800 hover:text-gray-900 transition-colors">
                    {{ __('Sign up') }}
                </a> --}}
            </p>
        @endif
    </div>

    {{-- Custom checkbox checked indicator via inline style --}}
    {{-- Custom checkbox checked indicator via inline style --}}
    <style>
        input[type="checkbox"]:checked {
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3E%3C/svg%3E");
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const iconEye = document.getElementById('icon-eye');
            const iconEyeSlash = document.getElementById('icon-eye-slash');
            if (input.type === 'password') {
                input.type = 'text';
                iconEye.classList.add('hidden');
                iconEyeSlash.classList.remove('hidden');
            } else {
                input.type = 'password';
                iconEye.classList.remove('hidden');
                iconEyeSlash.classList.add('hidden');
            }
        }
    </script>
</x-layouts::auth>
