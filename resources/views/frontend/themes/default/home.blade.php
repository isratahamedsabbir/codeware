<x-layouts::auth :title="$title ?? null" :noindex="false">
    <div class="flex flex-col items-center gap-4">
        @auth
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}"
                    class="rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-secondary transition-colors">
                    {{ __('Dashboard') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="rounded-md border border-white/20 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition-colors cursor-pointer">
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}"
                class="rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-secondary transition-colors">
                {{ __('Login') }}
            </a>
        @endauth
    </div>
</x-layouts::auth>
