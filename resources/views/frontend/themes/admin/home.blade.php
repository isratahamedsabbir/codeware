<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="flex min-h-screen items-center justify-center bg-white antialiased">

    <a href="{{ auth()->check() ? route('admin.dashboard') : route('login') }}"
        class="rounded-lg bg-primary px-8 py-4 text-base font-semibold text-white shadow-sm transition hover:opacity-90">
        {{ auth()->check() ? __('Dashboard') : __('Admin Login') }}
    </a>

    <livewire:frontend.chat-widget />

    @fluxScripts
</body>
</html>
