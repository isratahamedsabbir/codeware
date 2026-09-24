@php
    $base = 'w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-800 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20';
    $baseError = 'w-full rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm text-zinc-800 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-red-400 focus:ring-2 focus:ring-red-100';
    $icon = 'w-full rounded-xl border border-zinc-300 bg-white py-2.5 pl-10 pr-4 text-sm text-zinc-800 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20';
    $iconError = 'w-full rounded-xl border border-red-300 bg-white py-2.5 pl-10 pr-4 text-sm text-zinc-800 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-red-400 focus:ring-2 focus:ring-red-100';
    $field = fn (string $key, bool $withIcon = false): string => match (true) {
        $withIcon && $errors->has($key) => $iconError,
        $withIcon => $icon,
        $errors->has($key) => $baseError,
        default => $base,
    };

    $payIconPaths = [
        'banknotes' => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z',
        'smartphone' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'wallet' => 'M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3',
        'credit-card' => 'M2.25 8.25h19.5M4.5 4.5h15A2.25 2.25 0 0 1 21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75A2.25 2.25 0 0 1 4.5 4.5Zm0 0V18.75',
    ];
    $payIcon = [
        'cod' => 'banknotes',
        'bkash' => 'smartphone',
        'nagad' => 'smartphone',
        'rocket' => 'smartphone',
        'paypal' => 'wallet',
        'stripe' => 'credit-card',
        'sslcommerz' => 'credit-card',
        'applepay' => 'smartphone',
    ];
    $iconFor = fn (string $code): string => $payIcon[strtolower($code)] ?? 'credit-card';
@endphp

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-12">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 md:text-3xl">{{ __('Checkout') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Fill in your details to place the order.') }}</p>
        </div>
        {{-- Progress: same tracker as the cart page, cart step done. --}}
        <ol class="flex items-center gap-2 text-xs font-semibold sm:gap-3">
            <li class="flex items-center gap-2">
                <a href="{{ route('cart') }}" class="flex items-center gap-2 text-zinc-700 transition hover:text-brand">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                    {{ __('Cart') }}
                </a>
                <span class="h-px w-6 bg-emerald-400 sm:w-10"></span>
            </li>
            <li class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand text-xs font-bold text-white shadow-sm">2</span>
                <span class="text-zinc-900">{{ __('Checkout') }}</span>
                <span class="h-px w-6 bg-zinc-300 sm:w-10"></span>
            </li>
            <li class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-full border border-zinc-300 bg-white text-xs font-bold text-zinc-400">3</span>
                <span class="text-zinc-400">{{ __('Order placed') }}</span>
            </li>
        </ol>
    </div>

    @if ($count === 0)
        <div class="mx-auto flex max-w-md flex-col items-center rounded-card border border-dashed border-zinc-300 bg-white px-6 py-16 text-center shadow-sm">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-zinc-100 text-zinc-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </span>
            <h2 class="mt-4 text-lg font-semibold text-zinc-800">{{ __('Your cart is empty') }}</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Add a few products before checking out.') }}</p>
            <a href="{{ route('shop') }}"
                class="mt-6 inline-block rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                {{ __('Browse products') }}
            </a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_22rem] lg:items-start">
            <form id="checkout-form" wire:submit="placeOrder" class="space-y-6 lg:order-1">
                @error('cart')
                    <div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                    <header class="flex items-center gap-3 border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white shadow-sm">1</span>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-800">{{ __('Contact details') }}</h2>
                            <p class="text-xs text-zinc-500">{{ __('We use these to confirm and deliver your order.') }}</p>
                        </div>
                    </header>
                    <div class="grid gap-5 p-5 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Full name') }}</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                </span>
                                <input type="text" id="customer_name" wire:model="customer_name" autocomplete="name"
                                    placeholder="{{ __('e.g. Rahim Ahmed') }}" class="{{ $field('customer_name', true) }}">
                            </div>
                            @error('customer_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="customer_email" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Email address') }}</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                </span>
                                <input type="email" id="customer_email" wire:model="customer_email" autocomplete="email"
                                    placeholder="you@example.com" class="{{ $field('customer_email', true) }}">
                            </div>
                            @error('customer_email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="customer_phone" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Phone number') }}</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                    </svg>
                                </span>
                                <input type="text" id="customer_phone" wire:model="customer_phone" autocomplete="tel"
                                    placeholder="01XXXXXXXXX" class="{{ $field('customer_phone', true) }}">
                            </div>
                            @error('customer_phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="shipping_address" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Shipping address') }}</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-3 text-zinc-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm-6-5.25a6 6 0 0 0-6 6c0 4.23 4.678 7.09 6.888 8.21a3 3 0 0 0 2.223.002C17.322 17.84 21 17.23 21 11.25a6 6 0 0 0-6-6H9Z" />
                                    </svg>
                                </span>
                                <textarea id="shipping_address" wire:model="shipping_address" rows="3" autocomplete="street-address"
                                    placeholder="{{ __('House, street, area, city') }}" class="{{ $field('shipping_address', true) }}"></textarea>
                            </div>
                            @error('shipping_address') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                    <header class="flex items-center gap-3 border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white shadow-sm">2</span>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-800">{{ __('Shipping') }}</h2>
                            <p class="text-xs text-zinc-500">{{ __('Choose a delivery method for your order.') }}</p>
                        </div>
                    </header>
                    <div class="space-y-5 p-5">
                        <div>
                            @if ($shippingMethods)
                                <div class="grid gap-2.5 sm:grid-cols-2">
                                    @foreach ($shippingMethods as $method)
                                        <label class="group relative flex cursor-pointer items-center gap-3 rounded-card border border-zinc-300 bg-white px-4 py-3.5 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-zinc-400 has-[:checked]:border-brand has-[:checked]:bg-brand/5 has-[:checked]:ring-1 has-[:checked]:ring-brand/30 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand/40">
                                            <input type="radio" name="shipping_method_id" value="{{ $method['id'] }}" wire:model.live="shipping_method_id" class="sr-only">
                                            <span class="flex min-w-0 items-center gap-3">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 transition group-has-[:checked]:bg-brand/10 group-has-[:checked]:text-brand">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                                    </svg>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block truncate group-has-[:checked]:text-zinc-900">{{ $method['name'] }}</span>
                                                </span>
                                            </span>
                                            <span class="ml-auto flex shrink-0 items-center gap-2.5">
                                                @if ($method['cost'] > 0)
                                                    <span class="font-bold text-zinc-900">{{ format_money($method['cost']) }}</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-600">{{ __('Free') }}</span>
                                                @endif
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand opacity-0 transition group-has-[:checked]:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-zinc-500">{{ __('No shipping methods are available right now.') }}</p>
                            @endif
                            @error('shipping_method_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                    <header class="flex items-center gap-3 border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white shadow-sm">3</span>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-800">{{ __('Payment') }}</h2>
                            <p class="text-xs text-zinc-500">{{ __('Choose how you would like to pay.') }}</p>
                        </div>
                    </header>
                    <div class="space-y-5 p-5">
                        <div>
                            <div class="grid gap-2.5 sm:grid-cols-2">
                                @foreach ($paymentMethods as $code => $label)
                                    <label class="group relative flex cursor-pointer items-center gap-3 rounded-card border border-zinc-300 bg-white px-4 py-3.5 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-zinc-400 has-[:checked]:border-brand has-[:checked]:bg-brand/5 has-[:checked]:ring-1 has-[:checked]:ring-brand/30 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand/40">
                                        <input type="radio" name="payment_method" value="{{ $code }}" wire:model="payment_method" class="sr-only">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 transition group-has-[:checked]:bg-brand/10 group-has-[:checked]:text-brand">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $payIconPaths[$iconFor($code)] }}" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate group-has-[:checked]:text-zinc-900">{{ $label }}</span>
                                            @if ($code === \App\Support\PaymentMethods::COD)
                                                <span class="block text-xs font-medium text-zinc-500">{{ __('Pay when your order is delivered.') }}</span>
                                            @endif
                                        </span>
                                        <span class="ml-auto shrink-0 text-brand opacity-0 transition group-has-[:checked]:opacity-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('payment_method') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label for="coupon_code" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Coupon code') }}</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-12-2.25v-5.25m-2.25 5.25a1.5 1.5 0 0 0 0 3h21a1.5 1.5 0 0 0 0-3m0 0V9m0 6.75V9m-18-1.5a1.5 1.5 0 0 1-3 0V6.75a1.5 1.5 0 0 1 1.5-1.5h21a1.5 1.5 0 0 1 1.5 1.5v.75a1.5 1.5 0 0 1-3 0v-.75" />
                                        </svg>
                                    </span>
                                    <input type="text" id="coupon_code" wire:model="coupon_code" placeholder="{{ __('Optional') }}"
                                        class="uppercase {{ $field('coupon_code', true) }}">
                                </div>
                                @error('coupon_code') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="notes" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Order notes') }} <span class="font-normal text-zinc-400">({{ __('optional') }})</span></label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3.5 top-3 text-zinc-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </span>
                                    <textarea id="notes" wire:model="notes" rows="2" placeholder="{{ __('Delivery instructions, landmarks, preferred time...') }}"
                                        class="{{ $field('notes', true) }}"></textarea>
                                </div>
                                @error('notes') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </section>
            </form>

            <aside class="h-fit overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm lg:order-2 lg:sticky lg:top-24">
                <div class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-zinc-900">{{ __('Order summary') }}</h2>
                        <span class="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-bold text-brand">{{ trans_choice(':count item|:count items', $count, ['count' => $count]) }}</span>
                    </div>
                    <a href="{{ route('cart') }}" class="text-xs font-semibold text-zinc-500 underline-offset-4 transition hover:text-brand hover:underline">{{ __('Edit cart') }}</a>
                </div>

                <ul class="max-h-80 divide-y divide-zinc-100 overflow-y-auto px-5">
                    @foreach ($items as $item)
                        <li class="flex items-center gap-3 py-3.5">
                            @if ($item['image'])
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-14 w-14 shrink-0 rounded-xl border border-zinc-100 object-cover">
                            @endif
                            <div class="min-w-0 flex-1">
                                <a href="{{ $item['url'] }}" class="line-clamp-1 text-sm font-semibold text-zinc-800 hover:text-brand">{{ $item['name'] }}</a>
                                @if ($item['options_label'])
                                    <p class="text-xs text-zinc-500">{{ $item['options_label'] }}</p>
                                @endif
                                <p class="mt-0.5 text-xs text-zinc-500">{{ __('Qty: :quantity', ['quantity' => $item['quantity']]) }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-zinc-900">{{ $item['line_total_label'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="space-y-2.5 border-t border-zinc-100 px-5 py-4 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500">{{ __('Subtotal') }}</dt>
                        <dd class="font-semibold text-zinc-900">{{ format_money($subtotal) }}</dd>
                    </div>
                    @if ($vatEnabled && $vat > 0)
                        <div class="flex items-center justify-between">
                            <dt class="text-zinc-500">{{ $vatLabel }}</dt>
                            <dd class="font-semibold text-zinc-900">{{ format_money($vat) }}</dd>
                        </div>
                    @endif
                    @if ($shippingLabel !== '')
                        <div class="flex items-center justify-between">
                            <dt class="text-zinc-500">{{ __('Shipping') }} <span class="text-xs text-zinc-400">({{ $shippingLabel }})</span></dt>
                            @if ((float) $shipping > 0)
                                <dd class="font-semibold text-zinc-900">{{ format_money($shipping) }}</dd>
                            @else
                                <dd class="font-semibold text-emerald-600">{{ __('Free') }}</dd>
                            @endif
                        </div>
                    @endif
                    <div class="flex items-center justify-between rounded-card bg-brand/5 px-3.5 py-3">
                        <dt class="text-base font-bold text-zinc-900">{{ __('Total') }}</dt>
                        <dd class="text-xl font-extrabold text-brand">{{ format_money($total) }}</dd>
                    </div>
                </dl>

                <div class="px-5 pb-5">
                    <button type="submit" form="checkout-form" wire:loading.attr="disabled" wire:target="placeOrder"
                        class="flex w-full items-center justify-center gap-2 rounded-full bg-brand px-6 py-4 text-sm font-bold text-white shadow-md shadow-brand/20 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60">
                        <svg wire:loading wire:target="placeOrder" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                        </svg>
                        <svg wire:loading.remove wire:target="placeOrder" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span wire:loading.remove wire:target="placeOrder">{{ __('Place order') }}</span>
                        <span wire:loading wire:target="placeOrder">{{ __('Placing order...') }}</span>
                    </button>
                </div>
            </aside>
        </div>
    @endif
</main>