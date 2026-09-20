<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
</head>
<body class="bg-page-bg font-storefront text-zinc-800 antialiased">

@include('frontend.themes.ecommerce.partials.header')

@php
    $crumbs = [
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('My Account'), 'url' => route('account.dashboard')],
        ['label' => __('My Orders'), 'url' => null],
    ];
@endphp

<main>
    @include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="mb-6">
            <h1 class="text-lg font-bold uppercase tracking-wide text-zinc-800 md:text-2xl">{{ __('My Orders') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Everything you have ordered from us, newest first.') }}</p>
        </div>

        <div class="flex flex-col gap-6 md:flex-row">
            @include('frontend.themes.ecommerce.account.partials.account-nav')

            <div class="min-w-0 flex-1">
                @if ($orders->isEmpty())
                    <div class="rounded-lg border border-dashed border-zinc-300 bg-white px-6 py-16 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <h2 class="mt-4 text-lg font-semibold text-zinc-800">{{ __('No orders yet') }}</h2>
                        <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                            {{ __('When you place an order, it will show up here with its status and details.') }}
                        </p>
                        <a href="{{ route('shop') }}"
                            class="mt-6 inline-block rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                            {{ __('Browse products') }}
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
                        <table class="min-w-full divide-y divide-zinc-100 text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    <th class="px-5 py-3">{{ __('Order') }}</th>
                                    <th class="px-5 py-3">{{ __('Date') }}</th>
                                    <th class="px-5 py-3">{{ __('Items') }}</th>
                                    <th class="px-5 py-3">{{ __('Total') }}</th>
                                    <th class="px-5 py-3">{{ __('Status') }}</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($orders as $order)
                                    <tr class="hover:bg-gray-50/60">
                                        <td class="px-5 py-3.5 font-semibold text-zinc-800">{{ $order->order_number }}</td>
                                        <td class="px-5 py-3.5 text-gray-600">{{ $order->created_at?->toDisplay() }}</td>
                                        <td class="px-5 py-3.5 text-gray-600">{{ $order->items->sum('quantity') }}</td>
                                        <td class="px-5 py-3.5 font-bold text-zinc-800">{{ format_money($order->total) }}</td>
                                        <td class="px-5 py-3.5">
                                            @include('frontend.themes.ecommerce.account.partials.status-badge', ['status' => $order->status])
                                        </td>
                                        <td class="px-5 py-3.5 text-right">
                                            <a href="{{ route('account.orders.show', $order->order_number) }}"
                                                class="font-semibold text-brand hover:underline">
                                                {{ __('View') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
</body>
</html>