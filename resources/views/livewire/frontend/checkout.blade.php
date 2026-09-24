<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-12">
    <x-storefront.page-header :title="__('Checkout')" :subtitle="__('Fill in your details to place the order.')">
        <x-storefront.checkout-steps :current="2" />
    </x-storefront.page-header>

    @if ($count === 0)
        <x-storefront.empty-state icon="bag"
            :title="__('Your cart is empty')"
            :text="__('Add a few products before checking out.')"
            :action-href="route('shop')" :action-label="__('Browse products')" />
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_22rem] lg:items-start">
            <form id="checkout-form" wire:submit="placeOrder" class="space-y-6 lg:order-1">
                @error('cart')
                    <div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        <x-storefront.icon name="exclamation-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                {{-- 1. Contact details --}}
                <x-storefront.card :step="1" :title="__('Contact details')" :subtitle="__('We use these to confirm and deliver your order.')">
                    <div class="grid gap-5 p-5 sm:grid-cols-2">
                        <x-storefront.input name="customer_name" :label="__('Full name')" icon="user"
                            autocomplete="name" :placeholder="__('e.g. Rahim Ahmed')" />
                        <x-storefront.input name="customer_email" :label="__('Email address')" icon="mail"
                            type="email" autocomplete="email" placeholder="you@example.com" />
                        <div class="sm:col-span-2">
                            <x-storefront.input name="customer_phone" :label="__('Phone number')" icon="phone"
                                autocomplete="tel" placeholder="01XXXXXXXXX" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-storefront.input name="shipping_address" :label="__('Shipping address')" icon="map-pin" textarea
                                autocomplete="street-address" :placeholder="__('House, street, area, city')" />
                        </div>
                    </div>
                </x-storefront.card>

                {{-- 2. Shipping --}}
                <x-storefront.card :step="2" :title="__('Shipping')" :subtitle="__('Choose a delivery method for your order.')">
                    <div class="p-5">
                        @if ($shippingMethods)
                            <div class="grid gap-2.5 sm:grid-cols-2">
                                @foreach ($shippingMethods as $method)
                                    <x-storefront.choice-card name="shipping_method_id" :value="$method['id']" icon="truck" live>
                                        <span class="block truncate">{{ $method['name'] }}</span>
                                        <x-slot:aside>
                                            @if ($method['cost'] > 0)
                                                <span class="font-bold text-sf-heading">{{ format_money($method['cost']) }}</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-600">{{ __('Free') }}</span>
                                            @endif
                                        </x-slot:aside>
                                    </x-storefront.choice-card>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-zinc-500">{{ __('No shipping methods are available right now.') }}</p>
                        @endif
                        @error('shipping_method_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </x-storefront.card>

                {{-- 3. Payment --}}
                <x-storefront.card :step="3" :title="__('Payment')" :subtitle="__('Choose how you would like to pay.')">
                    <div class="space-y-5 p-5">
                        <div>
                            <div class="grid gap-2.5 sm:grid-cols-2">
                                @foreach ($paymentMethods as $code => $label)
                                    <x-storefront.choice-card name="payment_method" :value="$code" :icon="\App\Support\PaymentMethods::icon($code)">
                                        <span class="block truncate">{{ $label }}</span>
                                        @if ($code === \App\Support\PaymentMethods::COD)
                                            <span class="block text-xs font-medium text-zinc-500">{{ __('Pay when your order is delivered.') }}</span>
                                        @endif
                                    </x-storefront.choice-card>
                                @endforeach
                            </div>
                            @error('payment_method') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <x-storefront.input name="coupon_code" :label="__('Coupon code')" icon="ticket"
                            :placeholder="__('Optional')" class="uppercase" />
                        <x-storefront.input name="notes" :label="__('Order notes')" icon="document" textarea :rows="2" optional
                            :placeholder="__('Delivery instructions, landmarks, preferred time...')" />
                    </div>
                </x-storefront.card>
            </form>

            {{-- Order summary + Place order (submits the form above via form="checkout-form") --}}
            <x-storefront.card as="aside" :title="__('Order summary')" class="h-fit lg:order-2 lg:sticky lg:top-24">
                <x-slot:actions>
                    <x-storefront.count-badge :count="$count" />
                    <a href="{{ route('cart') }}" class="text-xs font-semibold text-zinc-500 underline-offset-4 transition hover:text-brand hover:underline">{{ __('Edit cart') }}</a>
                </x-slot:actions>

                <ul class="max-h-80 divide-y divide-zinc-100 overflow-y-auto px-5">
                    @foreach ($items as $item)
                        <li class="flex items-center gap-3 py-3.5">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-100 bg-zinc-50 text-zinc-300">
                                @if ($item['image'])
                                    <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover">
                                @else
                                    <x-storefront.icon name="box" stroke="1.5" class="h-6 w-6" />
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ $item['url'] }}" class="line-clamp-1 text-sm font-semibold text-sf-text hover:text-brand">{{ $item['name'] }}</a>
                                @if ($item['options_label'])
                                    <p class="text-xs text-zinc-500">{{ $item['options_label'] }}</p>
                                @endif
                                <p class="mt-0.5 text-xs text-zinc-500">{{ __('Qty: :quantity', ['quantity' => $item['quantity']]) }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-sf-heading">{{ $item['line_total_label'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="space-y-2.5 border-t border-zinc-100 px-5 py-4 text-sm">
                    <x-storefront.summary-row :label="__('Subtotal')">{{ format_money($subtotal) }}</x-storefront.summary-row>
                    @if ($vatEnabled && $vat > 0)
                        <x-storefront.summary-row :label="$vatLabel">{{ format_money($vat) }}</x-storefront.summary-row>
                    @endif
                    @if ($shippingLabel !== '')
                        <x-storefront.summary-row :value-class="(float) $shipping > 0 ? 'font-semibold tabular-nums text-sf-heading' : 'font-semibold text-emerald-600'">
                            <x-slot:label>{{ __('Shipping') }} <span class="text-xs text-zinc-400">({{ $shippingLabel }})</span></x-slot:label>
                            {{ (float) $shipping > 0 ? format_money($shipping) : __('Free') }}
                        </x-storefront.summary-row>
                    @endif
                    <x-storefront.total-row>{{ format_money($total) }}</x-storefront.total-row>
                </dl>

                <div class="px-5 pb-5">
                    <x-storefront.button form="checkout-form" icon="bag" loading="placeOrder" :loading-text="__('Placing order...')">
                        {{ __('Place order') }}
                    </x-storefront.button>
                </div>
            </x-storefront.card>
        </div>
    @endif
</main>
