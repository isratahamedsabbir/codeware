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
    $itemCount = (int) $order->items->sum('quantity');
    $paymentStatus = $order->payment_status ?: 'pending';
    $nextSteps = [
        ['title' => __('Order confirmed'), 'text' => __('We have received your order.'), 'done' => true],
        ['title' => __('Processing'), 'text' => __('We will call you on :phone to confirm the delivery details.', ['phone' => $order->customer_phone]), 'done' => false],
        ['title' => __('On the way'), 'text' => __('Your parcel is packed and handed over for delivery.'), 'done' => false],
        ['title' => __('Delivered'), 'text' => __('Enjoy your purchase!'), 'done' => false],
    ];
@endphp

<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
    {{-- Progress: every step of the purchase flow is done. --}}
    <ol class="mb-8 flex items-center justify-center gap-2 text-xs font-semibold sm:gap-3">
        @foreach ([__('Cart'), __('Checkout'), __('Order placed')] as $label)
            <li class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>
                <span class="text-zinc-700">{{ $label }}</span>
                @unless ($loop->last)
                    <span class="h-px w-6 bg-emerald-400 sm:w-10"></span>
                @endunless
            </li>
        @endforeach
    </ol>

    {{-- Success hero --}}
    <section class="relative overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-linear-to-r from-emerald-500 via-brand to-emerald-500"></div>
        <div class="flex flex-col items-center gap-6 px-6 py-8 text-center md:flex-row md:items-center md:justify-between md:px-10 md:text-left">
            <div class="flex flex-col items-center gap-5 md:flex-row">
                <span class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-sf-heading md:text-3xl">{{ __('Order placed!') }}</h1>
                    <p class="mt-1 text-sm text-zinc-500">
                        {{ __('Thank you, :name. Your order has been placed successfully.', ['name' => $order->customer_name]) }}
                    </p>
                    @if ($order->customer_email)
                        <p class="mt-0.5 text-xs text-zinc-400">{{ __('Order updates will be sent to :email.', ['email' => $order->customer_email]) }}</p>
                    @endif
                </div>
            </div>

            <div class="shrink-0 rounded-card border border-dashed border-brand/30 bg-brand/5 px-5 py-3 text-center">
                <p class="text-[11px] font-bold uppercase tracking-widest text-zinc-500">{{ __('Order number') }}</p>
                <p class="mt-0.5 font-mono text-xl font-extrabold tracking-wide text-brand">{{ $order->order_number }}</p>
            </div>
        </div>

        {{-- Invoice actions --}}
        <div class="flex flex-col gap-3 border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 sm:flex-row sm:items-center sm:justify-between md:px-10">
            <p class="flex items-center gap-2 text-sm text-zinc-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                {{ __('Keep a copy of your invoice for your records.') }}
            </p>
            <div class="flex flex-wrap gap-2.5">
                <a href="{{ $invoiceUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 rounded-full border border-zinc-300 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-brand hover:text-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                    </svg>
                    {{ __('View / print invoice') }}
                </a>
                <a href="{{ $invoiceDownloadUrl }}"
                    class="inline-flex items-center gap-2 rounded-full bg-sf-button px-5 py-2.5 text-sm font-semibold text-sf-button-text shadow-sm transition hover:opacity-90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('Download invoice (PDF)') }}
                </a>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
        {{-- Items + totals --}}
        <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
            <header class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                <h2 class="text-base font-bold text-sf-heading">{{ __('Order items') }}</h2>
                <span class="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-bold text-brand">{{ trans_choice(':count item|:count items', $itemCount, ['count' => $itemCount]) }}</span>
            </header>

            <ul class="divide-y divide-zinc-100 px-5">
                @foreach ($order->items as $item)
                    @php $image = $item->product?->featured_image; @endphp
                    <li class="flex items-center gap-4 py-4">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-card border border-zinc-100 bg-zinc-50 text-zinc-300">
                            @if ($image)
                                <img src="{{ $image }}" alt="{{ $item->item_name }}" class="h-full w-full object-cover">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                                </svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-semibold text-sf-text">{{ $item->item_name }}</p>
                            @if (! empty($item->variations))
                                <p class="mt-1 inline-flex rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600">{{ \App\Support\Cart::optionsLabel($item->variations) }}</p>
                            @endif
                            <p class="mt-1 text-xs text-zinc-500">{{ format_money($item->unit_price) }} &times; {{ $item->quantity }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-bold tabular-nums text-sf-heading">{{ format_money($item->line_total) }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="space-y-3 border-t border-zinc-100 px-5 py-5 text-sm">
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
                        <dt class="text-zinc-500">
                            {{ $order->vat_rate !== null ? $order->vat_rate.'% ' : '' }}{{ \App\Models\Setting::vatLabel() }}
                        </dt>
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
                <div class="flex items-center justify-between rounded-card bg-brand/5 px-4 py-3">
                    <dt class="text-base font-bold text-sf-heading">{{ __('Total') }}</dt>
                    <dd class="text-xl font-extrabold tabular-nums text-sf-price">{{ format_money($order->total) }}</dd>
                </div>
            </dl>
        </section>

        <div class="space-y-6">
            {{-- Order details --}}
            <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                <header class="border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                    <h2 class="text-base font-bold text-sf-heading">{{ __('Order details') }}</h2>
                </header>
                <dl class="space-y-3 px-5 py-4 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-zinc-500">{{ __('Date') }}</dt>
                        <dd class="font-medium text-sf-text">{{ $order->created_at?->format('d M Y, h:i A') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-zinc-500">{{ __('Status') }}</dt>
                        <dd>@include('frontend.themes.ecommerce.account.partials.status-badge', ['status' => $order->status ?: 'pending'])</dd>
                    </div>
                    @if ($order->payment_method)
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-zinc-500">{{ __('Payment') }}</dt>
                            <dd class="text-right font-medium text-sf-text">{{ \App\Support\PaymentMethods::label($order->payment_method) }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-zinc-500">{{ __('Payment status') }}</dt>
                        <dd>@include('frontend.themes.ecommerce.account.partials.status-badge', ['status' => $paymentStatus])</dd>
                    </div>
                </dl>
            </section>

            {{-- Delivery --}}
            <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                <header class="border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                    <h2 class="text-base font-bold text-sf-heading">{{ __('Delivery details') }}</h2>
                </header>
                <div class="space-y-3 px-5 py-4 text-sm">
                    <p class="font-semibold text-sf-heading">{{ $order->customer_name }}</p>
                    <p class="flex items-start gap-2 text-zinc-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        <span class="whitespace-pre-line">{{ $order->shipping_address }}</span>
                    </p>
                    <p class="flex items-center gap-2 text-zinc-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        {{ $order->customer_phone }}
                    </p>
                    @if ($order->customer_email)
                        <p class="flex items-center gap-2 break-all text-zinc-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            {{ $order->customer_email }}
                        </p>
                    @endif
                    @if ($order->notes)
                        <p class="rounded-card bg-zinc-50 px-3 py-2 text-xs text-zinc-600"><span class="font-semibold text-zinc-700">{{ __('Note') }}:</span> {{ $order->notes }}</p>
                    @endif
                </div>
            </section>

            {{-- What happens next --}}
            <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                <header class="border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                    <h2 class="text-base font-bold text-sf-heading">{{ __('What happens next?') }}</h2>
                </header>
                <ol class="px-5 py-4">
                    @foreach ($nextSteps as $step)
                        <li class="relative flex gap-3 pb-5 last:pb-0">
                            @unless ($loop->last)
                                <span class="absolute left-2.75 top-7 h-[calc(100%-1.75rem)] w-px bg-zinc-200"></span>
                            @endunless
                            <span @class([
                                'relative flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold',
                                'bg-emerald-500 text-white' => $step['done'],
                                'border border-zinc-300 bg-white text-zinc-400' => ! $step['done'],
                            ])>
                                @if ($step['done'])
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                            <div>
                                <p class="text-sm font-semibold {{ $step['done'] ? 'text-sf-heading' : 'text-zinc-700' }}">{{ $step['title'] }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <div class="flex flex-col gap-2.5">
                <a href="{{ route('shop') }}"
                    class="flex items-center justify-center gap-2 rounded-full bg-sf-button px-6 py-3 text-sm font-bold text-sf-button-text shadow-sm transition hover:opacity-90">
                    {{ __('Continue shopping') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                @auth
                    <a href="{{ route('account.orders') }}"
                        class="flex items-center justify-center rounded-full border border-zinc-300 bg-white px-6 py-3 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-brand hover:text-brand">
                        {{ __('My orders') }}
                    </a>
                @endauth
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
