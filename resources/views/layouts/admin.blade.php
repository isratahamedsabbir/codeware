<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? 'Admin') . ' — ' . config('app.name') }}</title>
    <link rel="icon" href="/favicon/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="/favicon/apple-touch-icon.png">
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

    <flux:sidebar sticky stashable class="admin-sidebar">
        <flux:sidebar.toggle class="lg:hidden self-end m-2 text-zinc-400 hover:text-zinc-200" icon="x-mark" />

        {{-- Logo --}}
        <div class="px-4 py-5 border-b border-zinc-800/40 shrink-0">
            <a href="{{ route('admin.dashboard') }}" wire:navigate.hover class="flex items-center gap-3">
                <div class="bg-white/95 rounded-xl px-3 py-1.5 shadow-md border border-white/10">
                    <img src="/agrosal_logo.png" alt="{{ config('app.name') }}">
                </div>

            </a>
        </div>

        {{-- Navigation --}}
        <nav id="admin-sidebar-nav" class="flex-1 overflow-y-auto px-2 py-4 space-y-0.5 custom-sidebar-nav">

            <a href="{{ route('admin.dashboard') }}" wire:navigate.hover
                class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'admin-nav-active' : '' }}">
                <flux:icon.home class="size-4.5 shrink-0" />
                <span>Overview</span>
            </a>

            <a href="{{ route('admin.profile') }}" wire:navigate.hover
                class="admin-nav-item {{ request()->routeIs('admin.profile') ? 'admin-nav-active' : '' }}">
                <flux:icon.user-circle class="size-4.5 shrink-0" />
                <span>My Profile</span>
            </a>

            <div x-data="{
                openGroup: '{{ request()->routeIs('admin.product-categories', 'admin.products')
                    ? 'products'
                    : (request()->routeIs('admin.post-categories', 'admin.posts', 'admin.tags')
                        ? 'blog'
                        : (request()->routeIs('admin.settings', 'admin.media-library')
                            ? 'library'
                            : (request()->routeIs('admin.contacts')
                                ? 'inquiries'
                                : (request()->routeIs('admin.pages')
                                    ? 'content'
                                    : 'products')))) }}',
                toggle(group) { this.openGroup = this.openGroup === group ? null : group }
            }">

                {{-- Products Group --}}
                <div class="nav-group">
                    <button @click="toggle('products')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Products</span>
                        <span x-text="openGroup === 'products' ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': openGroup !== 'products' }"
                        :style="openGroup === 'products' ? 'max-height: 500px; opacity: 1;' : ''">
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
                <div class="nav-group">
                    <button @click="toggle('blog')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Blog</span>
                        <span x-text="openGroup === 'blog' ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': openGroup !== 'blog' }"
                        :style="openGroup === 'blog' ? 'max-height: 500px; opacity: 1;' : ''">
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
                <div class="nav-group">
                    <button @click="toggle('library')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Library & System</span>
                        <span x-text="openGroup === 'library' ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': openGroup !== 'library' }"
                        :style="openGroup === 'library' ? 'max-height: 500px; opacity: 1;' : ''">
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
                    </div>
                </div>

                {{-- Inquiries Group --}}
                <div class="nav-group">
                    <button @click="toggle('inquiries')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Inquiries</span>
                        <span x-text="openGroup === 'inquiries' ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': openGroup !== 'inquiries' }"
                        :style="openGroup === 'inquiries' ? 'max-height: 500px; opacity: 1;' : ''">
                        <a href="{{ route('admin.contacts') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.contacts') ? 'admin-nav-active' : '' }}">
                            <flux:icon.inbox class="size-4.5 shrink-0" />
                            <span>Contacts</span>
                        </a>
                    </div>
                </div>

                {{-- Content Group --}}
                <div class="nav-group">
                    <button @click="toggle('content')"
                        class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none">
                        <span>Content</span>
                        <span x-text="openGroup === 'content' ? '−' : '+'"
                            class="text-zinc-500 text-sm font-bold leading-none"></span>
                    </button>
                    <div class="nav-group-items space-y-0.5" :class="{ 'collapsed': openGroup !== 'content' }"
                        :style="openGroup === 'content' ? 'max-height: 500px; opacity: 1;' : ''">
                        <a href="{{ route('admin.pages') }}" wire:navigate.hover
                            class="admin-nav-item {{ request()->routeIs('admin.pages') ? 'admin-nav-active' : '' }}">
                            <flux:icon.document class="size-4.5 shrink-0" />
                            <span>Pages</span>
                        </a>
                    </div>
                </div>

            </div>{{-- end accordion wrapper --}}

        </nav>
        {{-- Bottom Actions --}}
        <div class="shrink-0 border-t border-zinc-800/40 px-2 py-3 space-y-0.5">
            <button x-data x-on:click="document.getElementById('admin-logout-form').submit()"
                class="admin-nav-item w-full text-left cursor-pointer">
                <flux:icon.arrow-right-start-on-rectangle class="size-4.5 shrink-0" />
                <span>Log out</span>
            </button>
            <form id="admin-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                @csrf
            </form>
        </div>

        {{-- User Profile Card --}}
        <a href="{{ route('admin.profile') }}" wire:navigate.hover
            class="shrink-0 border-t border-zinc-800/40 px-3 py-3.5 flex items-center gap-3 hover:bg-zinc-800/30 transition-colors">
            @if (auth()->user()->photo_url)
                <img src="{{ auth()->user()->photo_url }}" alt="{{ auth()->user()->name }}"
                    class="size-9 rounded-xl object-cover shrink-0 shadow-md">
            @else
                <div
                    class="size-9 rounded-xl bg-gradient-to-br from-brand-green to-brand-blue flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-md">
                    {{ auth()->user()->initials() }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-zinc-100 truncate leading-tight">{{ auth()->user()->name }}</p>
                <p class="text-xs text-zinc-500 truncate mt-0.5">{{ auth()->user()->email }}</p>
            </div>
        </a>

    </flux:sidebar>

    {{-- Main content --}}
    <flux:main class="admin-main">
        <div class="p-4 md:p-6 max-w-[1600px] w-full mx-auto flex-1">
            {{ $slot }}
        </div>
    </flux:main>

    <div x-data x-on:notify.window="toastr.success($event.detail.message)"></div>

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
