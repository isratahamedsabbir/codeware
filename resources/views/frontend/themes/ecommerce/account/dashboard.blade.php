<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    @include('partials.custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('frontend.themes.ecommerce.partials.header')

@php
    $user = auth()->user();
    $crumbs = [
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('My Account'), 'url' => null],
    ];
@endphp

<main>
    @include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="mb-6">
            <h1 class="text-lg font-bold uppercase tracking-wide text-sf-text md:text-2xl">{{ __('My Account') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Welcome back, :name — here is what is happening with your orders.', ['name' => $user->name]) }}</p>
        </div>

        <div class="flex flex-col gap-6 md:flex-row">
            @include('frontend.themes.ecommerce.account.partials.account-nav')

            <div class="flex-1 space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-card border border-zinc-200 bg-white p-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-500">{{ __('Profile') }}</h2>
                        <p class="mt-2 text-base font-semibold text-sf-text">{{ $user->name }}</p>
                        <p class="text-sm text-gray-600">{{ $user->email }}</p>
                        <a href="{{ route('account.profile') }}"
                            class="mt-3 inline-block text-sm font-semibold text-brand hover:underline">
                            {{ __('Edit profile') }}
                        </a>
                    </div>

                    <div class="rounded-card border border-zinc-200 bg-white p-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-500">{{ __('Orders') }}</h2>
                        <p class="mt-2 text-3xl font-bold text-sf-text">{{ $orders->count() }}</p>
                        <p class="text-sm text-gray-600">{{ __('items in your recent orders') }}</p>
                        <a href="{{ route('account.orders') }}"
                            class="mt-3 inline-block text-sm font-semibold text-brand hover:underline">
                            {{ __('View all orders') }}
                        </a>
                    </div>
                </div>

                <section class="rounded-card border border-zinc-200 bg-white">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-500">{{ __('Recent orders') }}</h2>
                        @if ($orders->isNotEmpty())
                            <a href="{{ route('account.orders') }}" class="text-sm font-semibold text-brand hover:underline">{{ __('View all') }}</a>
                        @endif
                    </div>

                    @if ($orders->isEmpty())
                        <div class="px-5 py-12 text-center">
                            <p class="text-sm text-gray-500">{{ __('You have not placed any orders yet.') }}</p>
                            <a href="{{ route('shop') }}"
                                class="mt-4 inline-block rounded-full bg-sf-button px-6 py-2.5 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
                                {{ __('Start shopping') }}
                            </a>
                        </div>
                    @else
                        <ul class="divide-y divide-zinc-100">
                            @foreach ($orders as $order)
                                <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <a href="{{ route('account.orders.show', $order->order_number) }}"
                                            class="font-semibold text-sf-text hover:text-brand">
                                            {{ $order->order_number }}
                                        </a>
                                        <p class="text-xs text-gray-500">{{ $order->created_at?->toDisplay() }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        @include('frontend.themes.ecommerce.account.partials.status-badge', ['status' => $order->status])
                                        <span class="text-sm font-bold text-sf-text">{{ format_money($order->total) }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        </div>
    </div>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
@include('partials.custom-code-body')
</body>
</html>