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
        ['label' => __('My Orders'), 'url' => route('account.orders')],
        ['label' => $order->order_number, 'url' => null],
    ];
@endphp

<main>
    @include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-lg font-bold uppercase tracking-wide text-zinc-800 md:text-2xl">{{ __('Order :number', ['number' => $order->order_number]) }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Placed on :date', ['date' => $order->created_at?->toDisplay()]) }}
                </p>
            </div>
            @include('frontend.themes.ecommerce.account.partials.status-badge', ['status' => $order->status])
        </div>

        <div class="flex flex-col gap-6 md:flex-row">
            @include('frontend.themes.ecommerce.account.partials.account-nav')

            <div class="min-w-0 flex-1 space-y-6">
                <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                    <div class="border-b border-zinc-100 px-5 py-4">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-500">{{ __('Order items') }}</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-100 text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    <th class="px-5 py-3">{{ __('Item') }}</th>
                                    <th class="px-5 py-3 text-center">{{ __('Qty') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('Price') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td class="px-5 py-3.5">
                                            <span class="font-semibold text-zinc-800">{{ $item->item_name }}</span>
                                            @if ($item->product_id)
                                                <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-500">{{ __('Product') }}</span>
                                            @else
                                                <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-500">{{ __('Service') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 text-center text-gray-600">{{ $item->quantity }}</td>
                                        <td class="px-5 py-3.5 text-right text-gray-600">{{ format_money($item->unit_price) }}</td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-zinc-800">{{ format_money($item->line_total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-zinc-100">
                                    <th colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">{{ __('Subtotal') }}</th>
                                    <td class="px-5 py-3 text-right text-sm text-zinc-800">{{ format_money($order->subtotal) }}</td>
                                </tr>
                                @if ((float) $order->discount > 0)
                                    <tr>
                                        <th colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">
                                            {{ __('Discount') }}
                                            @if ($order->coupon_code)
                                                <span class="ml-1 text-xs font-medium text-zinc-400">({{ $order->coupon_code }})</span>
                                            @endif
                                        </th>
                                        <td class="px-5 py-3 text-right text-sm text-green-600">-{{ format_money($order->discount) }}</td>
                                    </tr>
                                @endif
                                <tr class="bg-gray-50">
                                    <th colspan="3" class="px-5 py-3.5 text-right text-sm font-bold text-zinc-800">{{ __('Total') }}</th>
                                    <td class="px-5 py-3.5 text-right text-sm font-bold text-brand">{{ format_money($order->total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <div class="grid gap-6 sm:grid-cols-2">
                    <section class="rounded-lg border border-zinc-200 bg-white p-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-500">{{ __('Shipping') }}</h2>
                        @if ($order->shipping_address)
                            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-zinc-700">{{ $order->shipping_address }}</p>
                            <p class="mt-2 text-sm text-gray-600">{{ $order->customer_name }}</p>
                            <p class="text-sm text-gray-600">{{ $order->customer_phone }}</p>
                        @else
                            <p class="mt-3 text-sm text-gray-500">{{ __('No shipping address — this order had nothing physical to deliver.') }}</p>
                        @endif
                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-500">{{ __('Payment') }}</h2>
                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">{{ __('Method') }}</span>
                                <span class="font-semibold text-zinc-800">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">{{ __('Status') }}</span>
                                <span>
                                    @include('frontend.themes.ecommerce.account.partials.status-badge', ['status' => $order->payment_status])
                                </span>
                            </div>
                        </div>

                        @if ($order->transactions->isNotEmpty())
                            <h3 class="mt-5 text-xs font-bold uppercase tracking-wide text-zinc-500">{{ __('Transactions') }}</h3>
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($order->transactions as $transaction)
                                    <li class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">
                                            {{ $transaction->payment_method }}
                                            @if ($transaction->reference)
                                                <span class="text-xs text-zinc-400">· {{ $transaction->reference }}</span>
                                            @endif
                                        </span>
                                        <span class="font-semibold text-zinc-800">{{ format_money($transaction->amount) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>

                @if ($order->notes)
                    <section class="rounded-lg border border-zinc-200 bg-white p-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-500">{{ __('Notes') }}</h2>
                        <p class="mt-3 text-sm leading-relaxed text-zinc-700">{{ $order->notes }}</p>
                    </section>
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