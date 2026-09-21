@php
    $fieldBase = 'w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-800 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/20';
    $fieldError = 'w-full rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm text-zinc-800 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-red-400 focus:ring-2 focus:ring-red-100';
    $fieldClass = fn (string $key) => $errors->has($key) ? $fieldError : $fieldBase;
@endphp

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-12">
    <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 md:text-3xl">{{ __('Checkout') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Fill in your details to place the order.') }}</p>
        </div>
        <a href="{{ route('cart') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-zinc-600 transition hover:text-brand">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            {{ __('Back to cart') }}
        </a>
    </div>

    @if ($count === 0)
        <div class="mx-auto flex max-w-md flex-col items-center rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-zinc-100 text-zinc-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
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
            <form wire:submit="placeOrder" class="space-y-6">
                @error('cart')
                    <div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                    <header class="flex items-center gap-3 border-b border-zinc-100 px-5 py-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand/10 text-sm font-bold text-brand">1</span>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-800">{{ __('Contact details') }}</h2>
                            <p class="text-xs text-zinc-500">{{ __('We use these to confirm and deliver your order.') }}</p>
                        </div>
                    </header>
                    <div class="grid gap-5 p-5 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Full name') }}</label>
                            <input type="text" id="customer_name" wire:model="customer_name" autocomplete="name"
                                placeholder="{{ __('e.g. Rahim Ahmed') }}" class="{{ $fieldClass('customer_name') }}">
                            @error('customer_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="customer_email" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Email address') }}</label>
                            <input type="email" id="customer_email" wire:model="customer_email" autocomplete="email"
                                placeholder="you@example.com" class="{{ $fieldClass('customer_email') }}">
                            @error('customer_email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="customer_phone" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Phone number') }}</label>
                            <input type="text" id="customer_phone" wire:model="customer_phone" autocomplete="tel"
                                placeholder="01XXXXXXXXX" class="{{ $fieldClass('customer_phone') }}">
                            @error('customer_phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="shipping_address" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Shipping address') }}</label>
                            <textarea id="shipping_address" wire:model="shipping_address" rows="3" autocomplete="street-address"
                                placeholder="{{ __('House, street, area, city') }}" class="{{ $fieldClass('shipping_address') }}"></textarea>
                            @error('shipping_address') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                    <header class="flex items-center gap-3 border-b border-zinc-100 px-5 py-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand/10 text-sm font-bold text-brand">2</span>
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-800">{{ __('Payment') }}</h2>
                            <p class="text-xs text-zinc-500">{{ __('Choose how you would like to pay.') }}</p>
                        </div>
                    </header>
                    <div class="space-y-5 p-5">
                        <div>
                            <span class="mb-2 block text-sm font-semibold text-zinc-800">{{ __('Payment method') }}</span>
                            <div class="grid gap-2.5 sm:grid-cols-2">
                                @foreach ($paymentMethods as $code => $label)
                                    <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-700 shadow-sm transition has-[:checked]:border-brand has-[:checked]:bg-brand/5 has-[:checked]:text-brand hover:border-zinc-400">
                                        <input type="radio" name="payment_method" value="{{ $code }}" wire:model="payment_method"
                                            class="h-4 w-4 border-zinc-300 text-brand focus:ring-2 focus:ring-brand/30">
                                        <span class="truncate">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('payment_method') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="coupon_code" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Coupon code') }}</label>
                            <input type="text" id="coupon_code" wire:model="coupon_code" placeholder="{{ __('Optional') }}"
                                class="{{ $fieldClass('coupon_code') }} uppercase">
                            @error('coupon_code') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="notes" class="mb-1.5 block text-sm font-semibold text-zinc-800">{{ __('Order notes') }} <span class="font-normal text-zinc-400">({{ __('optional') }})</span></label>
                            <textarea id="notes" wire:model="notes" rows="2"
                                placeholder="{{ __('Delivery instructions, landmarks, preferred time...') }}"
                                class="{{ $fieldClass('notes') }}"></textarea>
                            @error('notes') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
                    class="flex w-full items-center justify-center gap-2 rounded-full bg-brand px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60">
                    <svg wire:loading wire:target="placeOrder" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="placeOrder">{{ __('Place order') }}</span>
                    <span wire:loading wire:target="placeOrder">{{ __('Placing order...') }}</span>
                </button>

                <p class="flex items-center justify-center gap-1.5 text-xs text-zinc-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    {{ __('Your information is safe and secure.') }}
                </p>
            </form>

            <aside class="h-fit rounded-2xl border border-zinc-200 bg-white shadow-sm lg:sticky lg:top-24">
                <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
                    <h2 class="text-base font-bold text-zinc-900">{{ __('Order summary') }}</h2>
                    <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-bold text-zinc-600">{{ trans_choice(':count item|:count items', $count, ['count' => $count]) }}</span>
                </div>

                <ul class="max-h-80 divide-y divide-zinc-100 overflow-y-auto px-5">
                    @foreach ($items as $item)
                        <li class="flex items-center gap-3 py-3.5">
                            @if ($item['image'])
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-14 w-14 shrink-0 rounded-lg border border-zinc-100 object-cover">
                            @endif
                            <div class="min-w-0 flex-1">
                                <a href="{{ $item['url'] }}" class="line-clamp-1 text-sm font-semibold text-zinc-800 hover:text-brand">{{ $item['name'] }}</a>
                                @if ($item['options_label'])
                                    <p class="text-xs text-zinc-500">{{ $item['options_label'] }}</p>
                                @endif
                                <p class="text-xs text-zinc-500">{{ __('Qty: :quantity', ['quantity' => $item['quantity']]) }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-zinc-900">{{ $item['line_total_label'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="space-y-2.5 border-t border-zinc-100 px-5 py-4 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500">{{ __('Subtotal') }}</dt>
                        <dd class="font-semibold text-zinc-900">{{ format_money($subtotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-zinc-100 pt-3">
                        <dt class="text-base font-bold text-zinc-900">{{ __('Total') }}</dt>
                        <dd class="text-lg font-extrabold text-brand">{{ format_money($subtotal) }}</dd>
                    </div>
                </dl>
            </aside>
        </div>
    @endif
</main>
