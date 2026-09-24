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
    $crumbs = [
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('My Account'), 'url' => route('account.dashboard')],
        ['label' => __('My Orders'), 'url' => route('account.orders')],
        ['label' => $order->order_number, 'url' => null],
    ];

    // Same permanent signed invoice links as the order confirmation page.
    $invoiceUrl = \Illuminate\Support\Facades\URL::signedRoute('invoices.public.show', ['order' => $order->order_number]);
    $invoiceDownloadUrl = \Illuminate\Support\Facades\URL::signedRoute('invoices.public.download', ['order' => $order->order_number]);
    $itemCount = (int) $order->items->sum('quantity');
    $cardHeader = 'flex items-center justify-between gap-3 border-b border-zinc-100 bg-zinc-50/60 px-5 py-4';
@endphp

<main>
    @include('frontend.themes.ecommerce.partials.breadcrumbs', ['crumbs' => $crumbs])

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="flex flex-col gap-6 md:flex-row">
            @include('frontend.themes.ecommerce.account.partials.account-nav')

            <div class="min-w-0 flex-1 space-y-6">
                {{-- Order header + invoice --}}
                <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Order number') }}</p>
                            <h1 class="mt-0.5 font-mono text-2xl font-extrabold tracking-wide text-sf-heading">{{ $order->order_number }}</h1>
                            <p class="mt-1 text-sm text-zinc-500">{{ __('Placed on :date', ['date' => $order->created_at?->toDisplay()]) }}</p>
                        </div>
                        <div class="shrink-0">
                            @include('frontend.themes.ecommerce.account.partials.status-badge', ['status' => $order->status])
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-zinc-100 bg-zinc-50/60 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-zinc-600">{{ __('Keep a copy of your invoice for your records.') }}</p>
                        <div class="flex flex-wrap gap-2.5">
                            <a href="{{ $invoiceUrl }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-brand hover:text-brand">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                                </svg>
                                {{ __('View / print invoice') }}
                            </a>
                            <a href="{{ $invoiceDownloadUrl }}"
                                class="inline-flex items-center gap-2 rounded-full bg-sf-button px-4 py-2 text-sm font-semibold text-sf-button-text shadow-sm transition hover:opacity-90">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                {{ __('Download invoice (PDF)') }}
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Items + totals — same rows and total box as checkout --}}
                <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                    <header class="{{ $cardHeader }}">
                        <h2 class="text-base font-bold text-sf-heading">{{ __('Order items') }}</h2>
                        <span class="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-bold text-brand">{{ trans_choice(':count item|:count items', $itemCount, ['count' => $itemCount]) }}</span>
                    </header>

                    <ul class="divide-y divide-zinc-100 px-5">
                        @foreach ($order->items as $item)
                            @php $image = $item->product?->featured_image; @endphp
                            <li class="flex items-center gap-4 py-4">
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-100 bg-zinc-50 text-zinc-300">
                                    @if ($image)
                                        <img src="{{ $image }}" alt="{{ $item->item_name }}" class="h-full w-full object-cover">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                                        </svg>
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 text-sm font-semibold text-sf-heading">
                                        {{ $item->item_name }}
                                        @unless ($item->product_id)
                                            <span class="ml-1 rounded-full bg-zinc-100 px-2 py-0.5 align-middle text-[10px] font-semibold uppercase tracking-wide text-zinc-500">{{ __('Service') }}</span>
                                        @endunless
                                    </p>
                                    @if (! empty($item->variations))
                                        <p class="mt-1 inline-flex rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600">{{ \App\Support\Cart::optionsLabel($item->variations) }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-zinc-500">{{ format_money($item->unit_price) }} &times; {{ $item->quantity }}</p>
                                </div>
                                <span class="shrink-0 text-sm font-bold tabular-nums text-sf-heading">{{ format_money($item->line_total) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="space-y-2.5 border-t border-zinc-100 px-5 py-4 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-zinc-500">{{ __('Subtotal') }}</dt>
                            <dd class="font-semibold tabular-nums text-sf-heading">{{ format_money($order->subtotal) }}</dd>
                        </div>
                        @if ((float) $order->discount > 0)
                            <div class="flex items-center justify-between">
                                <dt class="text-zinc-500">
                                    {{ __('Discount') }}
                                    @if ($order->coupon_code)
                                        <span class="ml-1 rounded-md bg-emerald-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-emerald-700">{{ $order->coupon_code }}</span>
                                    @endif
                                </dt>
                                <dd class="font-semibold tabular-nums text-emerald-600">&minus; {{ format_money($order->discount) }}</dd>
                            </div>
                        @endif
                        @if ((float) $order->vat_amount > 0)
                            <div class="flex items-center justify-between">
                                <dt class="text-zinc-500">{{ $order->vat_rate !== null ? $order->vat_rate.'% ' : '' }}{{ \App\Models\Setting::vatLabel() }}</dt>
                                <dd class="font-semibold tabular-nums text-sf-heading">{{ format_money($order->vat_amount) }}</dd>
                            </div>
                        @endif
                        @if ($order->shipping_method || (float) $order->shipping_cost > 0)
                            <div class="flex items-center justify-between">
                                <dt class="text-zinc-500">
                                    {{ __('Shipping') }}
                                    @if ($order->shipping_method)
                                        <span class="text-xs text-zinc-400">({{ $order->shipping_method }})</span>
                                    @endif
                                </dt>
                                @if ((float) $order->shipping_cost > 0)
                                    <dd class="font-semibold tabular-nums text-sf-heading">{{ format_money($order->shipping_cost) }}</dd>
                                @else
                                    <dd class="font-semibold text-emerald-600">{{ __('Free') }}</dd>
                                @endif
                            </div>
                        @endif
                        <div class="flex items-center justify-between rounded-card bg-brand/5 px-3.5 py-3">
                            <dt class="text-base font-bold text-sf-heading">{{ __('Total') }}</dt>
                            <dd class="text-xl font-extrabold tabular-nums text-sf-price">{{ format_money($order->total) }}</dd>
                        </div>
                    </dl>
                </section>

                <div class="grid gap-6 lg:grid-cols-2">
                    {{-- Delivery --}}
                    <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                        <header class="{{ $cardHeader }}">
                            <h2 class="text-base font-bold text-sf-heading">{{ __('Delivery details') }}</h2>
                        </header>
                        <div class="space-y-3 px-5 py-4 text-sm">
                            @if ($order->shipping_address)
                                <p class="font-semibold text-sf-heading">{{ $order->customer_name }}</p>
                                <p class="flex items-start gap-2 text-zinc-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    <span class="whitespace-pre-line">{{ $order->shipping_address }}</span>
                                </p>
                                @if ($order->customer_phone)
                                    <p class="flex items-center gap-2 text-zinc-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                        </svg>
                                        {{ $order->customer_phone }}
                                    </p>
                                @endif
                                @if ($order->shipping_method)
                                    <p class="flex items-center gap-2 text-zinc-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                        </svg>
                                        {{ $order->shipping_method }}
                                    </p>
                                @endif
                            @else
                                <p class="text-zinc-500">{{ __('No shipping address — this order had nothing physical to deliver.') }}</p>
                            @endif
                        </div>
                    </section>

                    {{-- Payment --}}
                    <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                        <header class="{{ $cardHeader }}">
                            <h2 class="text-base font-bold text-sf-heading">{{ __('Payment') }}</h2>
                        </header>
                        <dl class="space-y-3 px-5 py-4 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-zinc-500">{{ __('Method') }}</dt>
                                <dd class="text-right font-medium text-sf-heading">{{ $order->payment_method ? \App\Support\PaymentMethods::label($order->payment_method) : '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-zinc-500">{{ __('Status') }}</dt>
                                <dd>@include('frontend.themes.ecommerce.account.partials.status-badge', ['status' => $order->payment_status ?: 'pending'])</dd>
                            </div>
                        </dl>

                        @if ($order->transactions->isNotEmpty())
                            <div class="border-t border-zinc-100 px-5 py-4">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Transactions') }}</p>
                                <ul class="space-y-1.5">
                                    @foreach ($order->transactions as $transaction)
                                        <li class="flex items-center justify-between gap-3 text-sm">
                                            <span class="min-w-0 truncate text-zinc-600">
                                                {{ $transaction->payment_method }}
                                                @if ($transaction->reference)
                                                    <span class="text-xs text-zinc-400">· {{ $transaction->reference }}</span>
                                                @endif
                                            </span>
                                            <span class="shrink-0 font-semibold tabular-nums text-sf-heading">{{ format_money($transaction->amount) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>
                </div>

                @if ($order->notes)
                    <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                        <header class="{{ $cardHeader }}">
                            <h2 class="text-base font-bold text-sf-heading">{{ __('Order notes') }}</h2>
                        </header>
                        <p class="px-5 py-4 text-sm leading-relaxed text-zinc-700">{{ $order->notes }}</p>
                    </section>
                @endif
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