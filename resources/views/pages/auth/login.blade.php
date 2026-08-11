<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-5 w-full">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        {{-- Session Status --}}
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            {{-- Email Address --}}
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-sm font-semibold text-gray-800 leading-tight">
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
                <label for="password" class="text-sm font-semibold text-gray-800 leading-tight">
                    {{ __('Password') }}
                </label>
                <div class="relative">
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        placeholder="{{ __('Password') }}"
                        class="
                            w-full rounded-md border border-gray-300 bg-white
                            px-3 py-2 text-sm font-normal text-gray-900
                            placeholder:text-gray-400
                            focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-gray-400
                            shadow-none
                            transition-colors duration-150
                        " />
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
                        bg-white checked:bg-gray-800 checked:border-gray-800
                        focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-1
                        shadow-none appearance-none cursor-pointer
                        transition-colors duration-150
                        relative
                    "
                    style="
                        --tw-shadow: none;
                        box-shadow: none;
                    " />
                <label for="remember"
                    class="text-sm font-normal text-gray-700 cursor-pointer select-none leading-tight">
                    {{ __('Remember me') }}
                </label>
            </div>

            {{-- Submit --}}
            <div class="pt-1">
                <button type="submit" data-test="login-button"
                    class="
                        w-full rounded-md bg-gray-900 px-4 py-2.5
                        text-sm font-semibold text-white
                        hover:bg-gray-700 active:bg-gray-950
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
                {{ __("Don't have an account?") }}
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
</x-layouts::auth>
