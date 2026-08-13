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
        $sidebarGroupItemsJson = json_encode([
            'products'     => [__('Product Categories'), __('Products')],
            'blog'         => [__('Post Categories'), __('Tags'), __('Posts')],
            'library'      => [__('Settings'), __('Media Library'), __('Email Templates'), __('Admin History')],
            'inquiries'    => [__('Contacts')],
            'content'      => [__('Pages')],
            'localization' => [__('Languages'), __('Translations')],
            'access'       => [__('Roles'), __('Permissions'), __('Users')],
        ]);
        $sidebarSinglesJson = json_encode([__('Overview')]);
    @endphp
    <flux:sidebar sticky stashable collapsible="desktop" class="admin-sidebar" x-data="{
        search: '',
        openGroup: '{{ request()->routeIs('admin.product-categories', 'admin.products')
            ? 'products'
            : (request()->routeIs('admin.post-categories', 'admin.posts', 'admin.tags')
                ? 'blog'
                : (request()->routeIs('admin.settings', 'admin.media-library', 'admin.email-templates', 'admin.history')
                    ? 'library'
                    : (request()->routeIs('admin.contacts')
                        ? 'inquiries'
                    : (request()->routeIs('admin.pages')
                        ? 'content'
                        : (request()->routeIs('admin.languages', 'admin.languages.*', 'admin.translations')
                            ? 'localization'
                            : (request()->routeIs('admin.roles', 'admin.roles.*', 'admin.permissions', 'admin.users', 'admin.users.*')
                                ? 'access'
                                : 'products')))))) }}',
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
                    <img src="{{ $siteIcon ?: '/agrosal_logo.png' }}" alt="{{ config('app.name') }}" class="h-7 w-auto">
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

            <div x-show="matches('Overview')">
                <a href="{{ route('admin.dashboard') }}" wire:navigate.hover
                    class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'admin-nav-active' : '' }}">
                    <flux:icon.home class="size-4.5 shrink-0" />
                    <span>Overview</span>
                </a>
            </div>

            <div>

                {{-- Products Group --}}
                <div class="nav-group" x-show="groupMatches('products')">
                    <button @click="toggle('products')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Products</span>
                        <span x-text="groupOpen('products') ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': !groupOpen('products') }"
                        :style="groupOpen('products') ? 'max-height: 500px; opacity: 1;' : ''">
                        <a href="{{ route('admin.product-categories') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.product-categories') ? 'admin-nav-active' : '' }}">
                            <flux:icon.squares-2x2 class="size-4.5 shrink-0" />
                            <span>Product Categories</span>
                        </a>
                        <a href="{{ route('admin.products') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.products') ? 'admin-nav-active' : '' }}">
                            <flux:icon.cube class="size-4.5 shrink-0" />
                            <span>Products</span>
                        </a>
                    </div>
                </div>

                {{-- Blog Group --}}
                <div class="nav-group" x-show="groupMatches('blog')">
                    <button @click="toggle('blog')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Blog</span>
                        <span x-text="groupOpen('blog') ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': !groupOpen('blog') }"
                        :style="groupOpen('blog') ? 'max-height: 500px; opacity: 1;' : ''">
                        <a href="{{ route('admin.post-categories') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.post-categories') ? 'admin-nav-active' : '' }}">
                            <flux:icon.squares-2x2 class="size-4.5 shrink-0" />
                            <span>Post Categories</span>
                        </a>
                        <a href="{{ route('admin.tags') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.tags') ? 'admin-nav-active' : '' }}">
                            <flux:icon.tag class="size-4.5 shrink-0" />
                            <span>Tags</span>
                        </a>
                        <a href="{{ route('admin.posts') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.posts') ? 'admin-nav-active' : '' }}">
                            <flux:icon.document-text class="size-4.5 shrink-0" />
                            <span>Posts</span>
                        </a>
                    </div>
                </div>

                {{-- Library & System Group --}}
                <div class="nav-group" x-show="groupMatches('library')">
                    <button @click="toggle('library')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Library & System</span>
                        <span x-text="groupOpen('library') ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': !groupOpen('library') }"
                        :style="groupOpen('library') ? 'max-height: 500px; opacity: 1;' : ''">
                        <a href="{{ route('admin.settings') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.settings') ? 'admin-nav-active' : '' }}">
                            <flux:icon.cog-6-tooth class="size-4.5 shrink-0" />
                            <span>Settings</span>
                        </a>
                        <a href="{{ route('admin.media-library') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.media-library') ? 'admin-nav-active' : '' }}">
                            <flux:icon.photo class="size-4.5 shrink-0" />
                            <span>Media Library</span>
                        </a>
                        <a href="{{ route('admin.email-templates') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.email-templates') ? 'admin-nav-active' : '' }}">
                            <flux:icon.envelope class="size-4.5 shrink-0" />
                            <span>Email Templates</span>
                        </a>
                        <a href="{{ route('admin.history') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.history') ? 'admin-nav-active' : '' }}">
                            <flux:icon.clock class="size-4.5 shrink-0" />
                            <span>Admin History</span>
                        </a>
                    </div>
                </div>

                {{-- Inquiries Group --}}
                <div class="nav-group" x-show="groupMatches('inquiries')">
                    <button @click="toggle('inquiries')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Inquiries</span>
                        <span x-text="groupOpen('inquiries') ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': !groupOpen('inquiries') }"
                        :style="groupOpen('inquiries') ? 'max-height: 500px; opacity: 1;' : ''">
                        <a href="{{ route('admin.contacts') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.contacts') ? 'admin-nav-active' : '' }}">
                            <flux:icon.inbox class="size-4.5 shrink-0" />
                            <span>Contacts</span>
                        </a>
                    </div>
                </div>

                {{-- Content Group --}}
                <div class="nav-group" x-show="groupMatches('content')">
                    <button @click="toggle('content')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Content</span>
                        <span x-text="groupOpen('content') ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': !groupOpen('content') }"
                        :style="groupOpen('content') ? 'max-height: 500px; opacity: 1;' : ''">
                        <a href="{{ route('admin.pages') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.pages') ? 'admin-nav-active' : '' }}">
                            <flux:icon.document class="size-4.5 shrink-0" />
                            <span>Pages</span>
                        </a>
                    </div>
                </div>

                {{-- Localization Group --}}
                <div class="nav-group" x-show="groupMatches('localization')">
                    <button @click="toggle('localization')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Localization</span>
                        <span x-text="groupOpen('localization') ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': !groupOpen('localization') }"
                        :style="groupOpen('localization') ? 'max-height: 500px; opacity: 1;' : ''">
                        <a href="{{ route('admin.languages') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.languages', 'admin.languages.*') ? 'admin-nav-active' : '' }}">
                            <flux:icon.language class="size-4.5 shrink-0" />
                            <span>Languages</span>
                        </a>
                        <a href="{{ route('admin.translations') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.translations') ? 'admin-nav-active' : '' }}">
                            <flux:icon.chat-bubble-left-right class="size-4.5 shrink-0" />
                            <span>Translations</span>
                        </a>
                    </div>
                </div>

                {{-- Access Control Group --}}
                <div class="nav-group" x-show="groupMatches('access')">
                    <button @click="toggle('access')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Access Control</span>
                        <span x-text="groupOpen('access') ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': !groupOpen('access') }"
                        :style="groupOpen('access') ? 'max-height: 500px; opacity: 1;' : ''">
                        <a href="{{ route('admin.roles') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.roles', 'admin.roles.*') ? 'admin-nav-active' : '' }}">
                            <flux:icon.shield-check class="size-4.5 shrink-0" />
                            <span>Roles</span>
                        </a>
                        <a href="{{ route('admin.permissions') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.permissions') ? 'admin-nav-active' : '' }}">
                            <flux:icon.lock-closed class="size-4.5 shrink-0" />
                            <span>Permissions</span>
                        </a>
                        <a href="{{ route('admin.users') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.users', 'admin.users.*') ? 'admin-nav-active' : '' }}">
                            <flux:icon.users class="size-4.5 shrink-0" />
                            <span>Users</span>
                        </a>
                    </div>
                </div>

            </div>{{-- end accordion wrapper --}}

            <div x-show="searching() && !anyMatch()" x-cloak
                class="px-3 py-8 text-center text-sm text-zinc-500">
                No menu items found for "<span x-text="search" class="text-zinc-400 font-medium"></span>"
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
