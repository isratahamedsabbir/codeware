<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

@php
    $crumbs = [
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('My Account'), 'url' => route('account.dashboard')],
        ['label' => __('Profile'), 'url' => null],
    ];
@endphp

<main>
    @include('theme-ecommerce::partials._breadcrumbs', ['crumbs' => $crumbs])

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="mb-6">
            <h1 class="text-lg font-bold uppercase tracking-wide text-sf-text md:text-2xl">{{ __('Profile') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Update your name, email, and password.') }}</p>
        </div>

        <div class="flex flex-col gap-6 md:flex-row">
            @include('theme-ecommerce::account.partials._account-nav')

            <div class="w-full max-w-xl flex-1">
                <div class="rounded-card border border-zinc-200 bg-white p-6">
                    <livewire:frontend.account.profile />
                </div>
            </div>
        </div>
    </div>
</main>

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>