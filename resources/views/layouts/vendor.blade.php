<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <meta name="robots" content="noindex, nofollow">
        @php
            $vendorPrimaryColor = \App\Models\Setting::get('primary_color', '#1e7bc4');
            $vendorSecondaryColor = \App\Models\Setting::get('secondary_color', '#7cc242');
        @endphp
        <style>
            /* Same brand colors as the admin panel (Settings → Theme → Backend),
               so cards/badges/buttons reusing --color-primary/--color-accent
               (the .admin-card family in app.css) match here too. */
            :root {
                --color-primary: {{ $vendorPrimaryColor }};
                --color-secondary: {{ $vendorSecondaryColor }};
                --color-accent: {{ $vendorPrimaryColor }};
                --color-accent-content: {{ $vendorPrimaryColor }};
            }
        </style>
    </head>
    <body class="min-h-screen bg-slate-50 antialiased dark:bg-zinc-900">
        @php
            $vendorNavItems = collect([
                ['label' => __('Dashboard'), 'icon' => 'home', 'route' => 'vendor.dashboard', 'current' => request()->routeIs('vendor.dashboard')],
                ['label' => __('Products'), 'icon' => 'cube', 'route' => 'vendor.products', 'current' => request()->routeIs('vendor.products*')],
                ['label' => __('Orders'), 'icon' => 'shopping-bag', 'route' => 'vendor.orders', 'current' => request()->routeIs('vendor.orders*')],
                ['label' => __('Chat'), 'icon' => 'chat-bubble-left-right', 'route' => 'vendor.chat', 'current' => request()->routeIs('vendor.chat')],
                ['label' => __('Profile'), 'icon' => 'user-circle', 'route' => 'vendor.profile', 'current' => request()->routeIs('vendor.profile')],
            ]);
        @endphp
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
            x-data="{
                search: '',
                matches(label) {
                    const q = this.search.trim().toLowerCase();
                    return !q || label.toLowerCase().includes(q);
                },
            }">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('vendor.dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            {{-- Nav search — filters the items below, same idea as the admin panel's sidebar search. --}}
            <div class="px-3 pt-1 pb-2">
                <div class="relative">
                    <flux:icon.magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                    <input type="text" x-model="search" placeholder="{{ __('Search menu...') }}" autocomplete="off"
                        class="w-full bg-white border border-zinc-200 rounded-lg pl-9 pr-8 py-1.5 text-sm text-zinc-800 placeholder:text-zinc-400 outline-none focus:border-primary transition dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-100">
                    <button type="button" x-show="search" x-cloak x-on:click="search = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 transition-colors">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Vendor Portal')" class="grid">
                    @foreach ($vendorNavItems as $item)
                        <div x-show="matches({{ \Illuminate\Support\Js::from($item['label']) }})">
                            <flux:sidebar.item :icon="$item['icon']" :href="route($item['route'])" :current="$item['current']" wire:navigate>
                                {{ $item['label'] }}
                            </flux:sidebar.item>
                        </div>
                    @endforeach
                </flux:sidebar.group>

                <div x-show="search && {{ \Illuminate\Support\Js::from($vendorNavItems->pluck('label')->values()) }}.every(l => !matches(l))"
                    x-cloak class="px-3 py-6 text-center text-xs text-zinc-500">
                    {{ __('No menu items found for') }} "<span x-text="search" class="text-zinc-400 font-medium"></span>"
                </div>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-vendor-user-menu class="hidden lg:block" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('vendor.profile')" icon="user-circle" wire:navigate>
                        {{ __('My Profile') }}
                    </flux:menu.item>

                    <form method="POST" action="{{ route('vendor.logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main class="vendor-main">
            <div class="flex-1 p-3 max-w-[1600px] w-full mx-auto">
                @unless ($hideHeading ?? false)
                    <div class="mb-5 flex items-center justify-between gap-4 flex-wrap">
                        <flux:heading size="xl">{{ $title ?? 'Vendor Portal' }}</flux:heading>
                        <div class="flex items-center gap-2 shrink-0 page-header-actions empty:hidden">
                            @stack('page-header-actions')
                        </div>
                    </div>
                @endunless

                {{ $slot }}
            </div>

            <footer class="shrink-0 border-t border-zinc-200/70 bg-white/60 backdrop-blur px-4 py-4 md:px-6 dark:border-zinc-700/70 dark:bg-zinc-900/60">
                <div class="max-w-[1600px] w-full mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-xs font-medium text-zinc-400">
                    <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('copyright_name', 'Codeware Limited') }}. All rights reserved.</p>
                    <p class="flex items-center gap-1.5 text-zinc-400">
                        <flux:icon.briefcase class="size-3.5" />
                        Vendor Portal
                    </p>
                </div>
            </footer>
        </flux:main>

        @fluxScripts
    </body>
</html>
