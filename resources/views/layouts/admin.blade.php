<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="{{ \App\Support\Theme::isDark() ? 'dark' : '' }}"
    style="--color-accent: {{ \App\Support\Theme::accent() }}; --color-accent-content: {{ \App\Support\Theme::accent() }};">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? 'Admin') . ' — ' . config('app.name') }}</title>
    @php
        $siteIcon = \App\Models\Setting::get('site_icon');
        $favicon = \App\Models\Setting::get('favicon');
    @endphp
    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}" sizes="any">
    @else
        <link rel="icon" href="/favicon/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
        <link rel="icon" href="/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
    @endif
    <link rel="apple-touch-icon" href="{{ $siteIcon ?: '/favicon/apple-touch-icon.png' }}">
    <link rel="manifest" href="/favicon/site.webmanifest">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet" href="//unpkg.com/jodit@4.1.16/es2021/jodit.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    <script src="//unpkg.com/jodit@4.1.16/es2021/jodit.min.js"></script>

    <style>
        .nav-group-items {
            overflow: hidden;
            transition: max-height 0.25s ease, opacity 0.2s ease;
        }

        .nav-group-items.collapsed {
            max-height: 0 !important;
            opacity: 0;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 antialiased font-sans">

    <script>
        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif
        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif
        @if (session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif
        @if (session('info'))
            toastr.info("{{ session('info') }}");
        @endif
    </script>

    @php
        // Precomputed as JSON and echoed (rather than using @js()/@directive calls inside the
        // x-data attribute below) because flux:sidebar is a Blade component tag — directives
        // with parentheses embedded in a component tag's attribute value get mangled by Blade's
        // attribute-string compilation and blow up Livewire's morph-marker precompiler regex.
        $sidebarMenu = \App\Models\MenuItem::menuForCurrentUser();

        $sidebarGroupItemsJson = json_encode(
            $sidebarMenu->filter(fn ($i) => $i->is_group)
                ->mapWithKeys(fn ($i) => [
                    $i->id => $i->children->map(fn ($c) => __($c->label))->values()->all(),
                ])
                ->all()
        );
        $sidebarSinglesJson = json_encode(
            $sidebarMenu->reject('is_group')->map(fn ($i) => __($i->label))->values()->all()
        );
        $sidebarOpenGroupId = optional($sidebarMenu->first(fn ($g) => $g->is_group && $g->children->contains(
            fn ($c) => $c->route_name && (request()->routeIs($c->route_name) || request()->routeIs($c->route_name.'.*'))
        )))->id;
    @endphp
    <flux:sidebar sticky stashable collapsible="desktop" class="admin-sidebar" x-data="{
        search: '',
        openGroup: {{ $sidebarOpenGroupId ?? 'null' }},
        groupItems: {{ $sidebarGroupItemsJson }},
        query() { return this.search.trim().toLowerCase(); },
        searching() { return this.query() !== ''; },
        matches(label) {
            const q = this.query();
            return !q || label.toLowerCase().includes(q);
        },
        groupMatches(group) {
            const q = this.query();
            if (!q) return true;
            return this.groupItems[group].some(l => l.toLowerCase().includes(q));
        },
        groupOpen(group) {
            return this.groupMatches(group) && (this.searching() ? true : this.openGroup === group);
        },
        anyMatch() {
            const q = this.query();
            if (!q) return true;
            const singles = {{ $sidebarSinglesJson }};
            return singles.some(l => l.toLowerCase().includes(q)) ||
                Object.values(this.groupItems).flat().some(l => l.toLowerCase().includes(q));
        },
        toggle(group) { this.openGroup = this.openGroup === group ? null : group }
    }">
        <flux:sidebar.toggle class="lg:hidden self-end m-2 text-zinc-400 hover:text-zinc-200" icon="x-mark" />

        {{-- Logo --}}
        <div class="px-4 py-5 border-b border-zinc-800/40 shrink-0">
            <a href="{{ route('admin.dashboard') }}" wire:navigate.hover class="flex items-center gap-3 admin-sidebar-logo">
                <div class="bg-white/95 rounded-xl px-3 py-1.5 shadow-md border border-white/10">
                    <img src="{{ $siteIcon ?: '/codeware_logo.png' }}" alt="{{ config('app.name') }}" class="h-7 w-auto">
                </div>
            </a>
        </div>

        {{-- Menu search --}}
        <div class="px-2 py-3 border-b border-zinc-800/40 shrink-0 admin-sidebar-search">
            <div class="relative">
                <flux:icon.magnifying-glass
                    class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-500 pointer-events-none" />
                <input type="text" x-model="search" placeholder="{{ __('Search menu...') }}" autocomplete="off"
                    class="w-full bg-zinc-800/60 border border-zinc-700/60 rounded-lg pl-9 pr-8 py-2 text-sm text-zinc-200 placeholder:text-zinc-500 outline-none focus:border-[#7cc242]/60 focus:ring-1 focus:ring-[#7cc242]/40 transition">
                <button type="button" x-show="search" x-on:click="search = ''"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-300 transition-colors">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>

        {{-- Navigation --}}
        <nav id="admin-sidebar-nav" class="flex-1 overflow-y-auto px-2 py-4 space-y-0.5 custom-sidebar-nav">

            @foreach ($sidebarMenu as $item)
                @if ($item->is_group)
                    <div class="nav-group" x-show="groupMatches({{ $item->id }})">
                        <button @click="toggle({{ $item->id }})"
                            class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                            <span>{{ __($item->label) }}</span>
                            <span x-text="groupOpen({{ $item->id }}) ? '−' : '+'"
                                class="text-zinc-500 text-sm font-bold leading-none"></span>
                        </button>
                        <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': !groupOpen({{ $item->id }}) }"
                            :style="groupOpen({{ $item->id }}) ? 'max-height: 500px; opacity: 1;' : ''">
                            @foreach ($item->children as $child)
                                @include('partials.admin-nav-link', ['link' => $child])
                            @endforeach
                        </div>
                    </div>
                @else
                    <div x-show="matches({{ \Illuminate\Support\Js::from(__($item->label)) }})">
                        @include('partials.admin-nav-link', ['link' => $item])
                    </div>
                @endif
            @endforeach

            <div x-show="searching() && !anyMatch()" x-cloak
                class="px-3 py-8 text-center text-sm text-zinc-500">
                {{ __('No menu items found for') }} "<span x-text="search" class="text-zinc-400 font-medium"></span>"
            </div>

        </nav>

    </flux:sidebar>

    {{-- Main content --}}
    <flux:main class="admin-main">
        <flux:header sticky class="admin-header shrink-0">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />
            <flux:sidebar.collapse class="max-lg:hidden me-1" />
            <flux:button variant="subtle" square x-data x-on:click="location.reload()" icon="arrow-path"
                aria-label="Refresh" class="max-lg:hidden me-1" />
            <flux:button variant="subtle" square :href="config('app.frontend_url')" icon="arrow-top-right-on-square"
                target="_blank" aria-label="Open frontend" class="max-lg:hidden me-1" />

            <div class="flex-1"></div>

            <x-admin-quick-menu class="ml-3" />

            <livewire:admin.locale-switcher class="ml-3" />

            <livewire:admin.theme-switcher class="ml-3" />

            <livewire:admin.notifications.bell class="ml-3" />

            <x-admin-user-menu class="ml-3" />
        </flux:header>

        <div class="flex-1 p-4 md:p-6 max-w-[1600px] w-full mx-auto">
            @unless ($hidePageHeading ?? false)
                <div class="mb-6">
                    <flux:heading size="xl">{{ $title ?? 'Admin' }}</flux:heading>
                    <div class="mt-2">
                        @include('partials.admin-breadcrumbs')
                    </div>
                </div>
            @endunless

            {{ $slot }}
        </div>

        <footer class="shrink-0 border-t border-zinc-200/80 bg-white/70 backdrop-blur px-6 py-4">
            <div
                class="max-w-[1600px] w-full mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-zinc-500">
                <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                <p class="flex items-center gap-1.5">
                    <flux:icon.cube class="size-3.5 text-zinc-400" />
                    v{{ config('app.version') }}
                </p>
            </div>
        </footer>
    </flux:main>

    <div x-data x-on:notify.window="toastr.success($event.detail.message)"></div>

    {{-- Apply the global admin theme (dark class + accent color) --}}
    <script>
        function applyAdminTheme(mode, accent) {
            const el = document.documentElement;
            el.classList.toggle('dark', mode === 'dark');
            if (accent) {
                el.style.setProperty('--color-accent', accent);
                el.style.setProperty('--color-accent-content', accent);
            }
        }

        document.addEventListener('livewire:navigated', () => {
            applyAdminTheme('{{ \App\Support\Theme::isDark() ? 'dark' : 'light' }}');
        });

        window.addEventListener('theme:toggled', (e) => {
            applyAdminTheme(e.detail.mode);
        });

        window.addEventListener('admin-theme-changed', (e) => {
            applyAdminTheme(e.detail.mode, e.detail.accent);
        });
    </script>

    @fluxScripts

    {{-- Auto-scroll sidebar so active menu item is always visible --}}
    <script>
        function scrollActiveNavItem() {
            const nav = document.getElementById('admin-sidebar-nav');
            if (!nav) return;

            const activeItem = nav.querySelector('.admin-nav-active');
            if (!activeItem) return;

            // Get true position of active item inside the scrollable nav container
            const navRect = nav.getBoundingClientRect();
            const itemRect = activeItem.getBoundingClientRect();

            // item position relative to nav visible area + current scrollTop = absolute position in scroll container
            const absoluteTop = nav.scrollTop + (itemRect.top - navRect.top);

            // Scroll so active item is near top with 16px padding
            nav.scrollTop = absoluteTop - 16;
        }

        // 1. Normal full page load
        document.addEventListener('DOMContentLoaded', scrollActiveNavItem);

        // 2. Livewire wire:navigate — fires after each Livewire page navigation
        document.addEventListener('livewire:navigated', scrollActiveNavItem);
    </script>
</body>

</html>
