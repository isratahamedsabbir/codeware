<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold uppercase tracking-wide text-zinc-800 md:text-3xl">{{ __('Checkout') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('Fill in your details to place the order.') }}</p>
    </div>

    @if ($count === 0)
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-20 text-center">
            <h2 class="text-lg font-semibold text-zinc-800">{{ __('Your cart is empty') }}</h2>
            <a href="{{ route('shop') }}"
                class="mt-6 inline-block rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                {{ __('Browse products') }}
            </a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_22rem]">
            <form wire:submit="placeOrder" class="space-y-6">
                @error('cart')
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
                @enderror

                <fieldset class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                    <legend class="px-2 text-sm font-bold uppercase tracking-wide text-zinc-500">{{ __('Contact details') }}</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="mb-1.5 block text-sm font-semibold text-zinc-900">{{ __('Full name') }}</label>
                            <input type="text" id="customer_name" wire:model="customer_name"
                                class="w-full rounded-xl border-zinc-200 px-4 py-2.5 text-sm">
                            @error('customer_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="customer_email" class="mb-1.5 block text-sm font-semibold text-zinc-900">{{ __('Email address') }}</label>
                            <input type="email" id="customer_email" wire:model="customer_email"
                                class="w-full rounded-xl border-zinc-200 px-4 py-2.5 text-sm">
                            @error('customer_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="customer_phone" class="mb-1.5 block text-sm font-semibold text-zinc-900">{{ __('Phone number') }}</label>
                            <input type="text" id="customer_phone" wire:model="customer_phone"
                                class="w-full rounded-xl border-zinc-200 px-4 py-2.5 text-sm">
                            @error('customer_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="shipping_address" class="mb-1.5 block text-sm font-semibold text-zinc-900">{{ __('Shipping address') }}</label>
                            <textarea id="shipping_address" wire:model="shipping_address" rows="3"
                                class="w-full rounded-xl border-zinc-200 px-4 py-2.5 text-sm"></textarea>
                            @error('shipping_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                <fieldset class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                    <legend class="px-2 text-sm font-bold uppercase tracking-wide text-zinc-500">{{ __('Payment') }}</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="payment_method" class="mb-1.5 block text-sm font-semibold text-zinc-900">{{ __('Payment method') }}</label>
                            <select id="payment_method" wire:model="payment_method"
                                class="w-full rounded-xl border-zinc-200 px-4 py-2.5 text-sm">
                                @foreach ($paymentMethods as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="coupon_code" class="mb-1.5 block text-sm font-semibold text-zinc-900">{{ __('Coupon code') }}</label>
                            <input type="text" id="coupon_code" wire:model="coupon_code"
                                class="w-full rounded-xl border-zinc-200 px-4 py-2.5 text-sm uppercase">
                            @error('coupon_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="notes" class="mb-1.5 block text-sm font-semibold text-zinc-900">{{ __('Order notes (optional)') }}</label>
                            <textarea id="notes" wire:model="notes" rows="2"
                                class="w-full rounded-xl border-zinc-200 px-4 py-2.5 text-sm"></textarea>
                            @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-full bg-brand px-6 py-3.5 text-sm font-bold text-white transition hover:opacity-90 disabled:opacity-60">
                    {{ __('Place order') }}
                </button>
            </form>

            <aside class="h-fit rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                <h2 class="text-base font-bold text-zinc-900">{{ __('Order summary') }}</h2>
                <ul class="mt-4 divide-y divide-zinc-100">
                    @foreach ($items as $item)
                        <li class="flex items-center gap-3 py-3">
                            @if ($item['image'])
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-14 w-14 shrink-0 rounded-lg object-cover">
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
                <dl class="mt-2 space-y-2 border-t border-zinc-100 pt-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500">{{ __('Subtotal') }}</dt>
                        <dd class="font-semibold text-zinc-900">{{ format_money($subtotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-zinc-100 pt-3">
                        <dt class="text-base font-bold text-zinc-900">{{ __('Total') }}</dt>
                        <dd class="text-lg font-extrabold text-zinc-900">{{ format_money($subtotal) }}</dd>
                    </div>
                </dl>
                <a href="{{ route('cart') }}"
                    class="mt-4 block text-center text-sm font-semibold text-zinc-600 underline-offset-4 transition hover:text-brand hover:underline">
                    {{ __('Back to cart') }}
                </a>
            </aside>
        </div>
    @endif
</main>