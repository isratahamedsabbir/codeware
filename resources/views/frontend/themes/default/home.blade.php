<x-layouts::auth :title="$title ?? null" :noindex="false">
    <div class="flex flex-col items-center gap-4">
        @auth
            <div class="flex items-center gap-3 flex-wrap justify-center">
                @can('access-admin')
                    <a href="{{ url('/admin') }}"
                        class="rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-secondary transition-colors">
                        {{ __('Admin Dashboard') }}
                    </a>
                @endcan
                @can('access-vendor-portal')
                    <a href="{{ route('vendor.dashboard') }}"
                        class="rounded-md border border-white/20 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition-colors">
                        {{ __('Vendor Dashboard') }}
                    </a>
                @endcan
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="rounded-md border border-white/20 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition-colors cursor-pointer">
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        @else
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}"
                    class="rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-secondary transition-colors">
                    {{ __('Admin Login') }}
                </a>
                <a href="{{ route('vendor.login') }}"
                    class="rounded-md border border-white/20 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition-colors">
                    {{ __('Vendor Login') }}
                </a>
            </div>
        @endauth
    </div>
</x-layouts::auth>
