<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
</head>
<body class="bg-page-bg font-storefront text-zinc-800 antialiased">

@include('frontend.themes.ecommerce.partials.header')

<main class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
    <div class="rounded-2xl border border-zinc-100 bg-white p-6 text-center shadow-sm sm:p-10">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>

        <h1 class="mt-4 text-2xl font-extrabold text-zinc-900">{{ __('Order placed!') }}</h1>
        <p class="mt-2 text-sm text-zinc-500">
            {{ __('Thank you, :name. Your order has been placed successfully.', ['name' => $order->customer_name]) }}
        </p>

        <div class="mx-auto mt-6 max-w-sm rounded-xl border border-zinc-100 bg-zinc-50 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide text-zinc-400">{{ __('Order number') }}</p>
            <p class="mt-1 text-xl font-extrabold text-brand">{{ $order->order_number }}</p>
        </div>

        <ul class="mt-6 divide-y divide-zinc-100 text-left">
            @foreach ($order->items as $item)
                <li class="flex flex-col gap-1 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <span class="font-medium text-zinc-800">
                        {{ $item->item_name }}
                        @if (! empty($item->variations))
                            <span class="block text-xs font-normal text-zinc-400">{{ \App\Support\Cart::optionsLabel($item->variations) }}</span>
                        @endif
                        <span class="text-zinc-400">&times; {{ $item->quantity }}</span>
                    </span>
                    <span class="font-semibold text-zinc-900">{{ format_money($item->line_total) }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="mx-auto mt-2 max-w-md space-y-2 border-t border-zinc-100 pt-4 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-zinc-500">{{ __('Subtotal') }}</dt>
                <dd class="font-semibold text-zinc-900">{{ format_money($order->subtotal) }}</dd>
            </div>
            @if ((float) $order->discount > 0)
                <div class="flex items-center justify-between">
                    <dt class="text-zinc-500">{{ __('Discount') }}</dt>
                    <dd class="font-semibold text-emerald-600">&minus; {{ format_money($order->discount) }}</dd>
                </div>
            @endif
            @if ((float) $order->vat_amount > 0)
                <div class="flex items-center justify-between">
                    <dt class="text-zinc-500">
                        {{ $order->vat_rate !== null ? $order->vat_rate.'% ' : '' }}{{ \App\Models\Setting::vatLabel() }}
                    </dt>
                    <dd class="font-semibold text-zinc-900">{{ format_money($order->vat_amount) }}</dd>
                </div>
            @endif
            @if ((float) $order->shipping_cost > 0)
                <div class="flex items-center justify-between">
                    <dt class="text-zinc-500">
                        {{ __('Shipping') }}
                        @if ($order->shipping_method)
                            <span class="text-zinc-400">({{ $order->shipping_method }})</span>
                        @endif
                    </dt>
                    <dd class="font-semibold text-zinc-900">{{ format_money($order->shipping_cost) }}</dd>
                </div>
            @endif
            <div class="flex items-center justify-between border-t border-zinc-100 pt-3">
                <dt class="text-base font-bold text-zinc-900">{{ __('Total') }}</dt>
                <dd class="text-xl font-extrabold text-zinc-900">{{ format_money($order->total) }}</dd>
            </div>
            @if ($order->payment_method)
                <div class="flex items-center justify-between">
                    <dt class="text-zinc-500">{{ __('Payment') }}</dt>
                    <dd class="font-medium text-zinc-800">{{ \App\Support\PaymentMethods::label($order->payment_method) }}</dd>
                </div>
            @endif
        </dl>

        <p class="mt-6 text-sm text-zinc-500">
            {{ __('We will contact you on :phone to confirm delivery.', ['phone' => $order->customer_phone]) }}
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('shop') }}"
                class="rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                {{ __('Continue shopping') }}
            </a>
            @auth
                <a href="{{ route('account.orders') }}"
                    class="rounded-full border border-zinc-200 px-6 py-2.5 text-sm font-semibold text-zinc-700 transition hover:border-brand hover:text-brand">
                    {{ __('My orders') }}
                </a>
            @endauth
        </div>
    </div>
</main>

@include('frontend.themes.ecommerce.partials.footer')

<livewire:frontend.chat-widget />

@fluxScripts
</body>
</html>