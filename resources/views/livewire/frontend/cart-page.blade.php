<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-12">
    <x-storefront.page-header :title="__('My cart')" :subtitle="__('Review your items before checkout.')">
        <x-storefront.checkout-steps :current="1" />
    </x-storefront.page-header>

    @if ($count === 0)
        <x-storefront.empty-state icon="cart"
            :title="__('Your cart is empty')"
            :text="__('Add some products from the shop to get started.')"
            :action-href="route('shop')" :action-label="__('Browse products')" />
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_22rem] lg:items-start">
            <x-storefront.card :title="__('Cart items')">
                <x-slot:actions><x-storefront.count-badge :count="$count" /></x-slot:actions>

                {{-- Column headings (desktop only — rows stack on small screens). --}}
                <div class="hidden grid-cols-[1fr_7rem_8rem_7rem_2.5rem] items-center gap-4 border-b border-zinc-100 px-5 py-3 text-xs font-bold uppercase tracking-wide text-zinc-500 md:grid">
                    <span>{{ __('Product') }}</span>
                    <span class="text-right">{{ __('Price') }}</span>
                    <span class="text-center">{{ __('Quantity') }}</span>
                    <span class="text-right">{{ __('Total') }}</span>
                    <span class="sr-only">{{ __('Remove') }}</span>
                </div>

                <ul class="divide-y divide-zinc-100" wire:loading.class="opacity-60" wire:target="increase,decrease,remove,clear">
                    @foreach ($items as $item)
                        <li wire:key="cart-line-{{ $item['key'] }}"
                            class="grid grid-cols-[5rem_1fr] gap-4 px-5 py-5 md:grid-cols-[1fr_7rem_8rem_7rem_2.5rem] md:items-center">
                            {{-- Product --}}
                            <div class="contents md:flex md:min-w-0 md:items-center md:gap-4">
                                <a href="{{ $item['url'] }}" class="row-span-2 flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-100 bg-zinc-50 text-zinc-300 md:row-span-1">
                                    @if ($item['image'])
                                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover transition duration-300 hover:scale-105">
                                    @else
                                        <x-storefront.icon name="box" stroke="1.5" class="h-7 w-7" />
                                    @endif
                                </a>

                                <div class="min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <a href="{{ $item['url'] }}" class="line-clamp-2 text-[15px] font-semibold text-sf-text transition-colors hover:text-brand">{{ $item['name'] }}</a>
                                        {{-- Mobile remove --}}
                                        <button type="button" wire:click="remove('{{ $item['key'] }}')" aria-label="{{ __('Remove :name', ['name' => $item['name']]) }}"
                                            class="-mr-1.5 -mt-1 shrink-0 rounded-full p-1.5 text-zinc-400 transition hover:bg-red-50 hover:text-red-600 md:hidden">
                                            <x-storefront.icon name="trash" stroke="1.8" class="h-4.5 w-4.5" />
                                        </button>
                                    </div>
                                    @if ($item['options_label'])
                                        <p class="mt-1 inline-flex rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600">{{ $item['options_label'] }}</p>
                                    @endif
                                    @if ($item['discount_label'])
                                        <p class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 md:flex">
                                            <x-storefront.icon name="tag" class="h-3.5 w-3.5" />
                                            {{ __('On sale') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Price --}}
                            <div class="col-start-2 flex items-baseline gap-2 text-sm md:col-start-auto md:flex-col md:items-end md:gap-0">
                                <span class="font-semibold text-sf-heading">{{ $item['discount_label'] ?: $item['unit_price_label'] }}</span>
                                @if ($item['discount_label'])
                                    <span class="text-xs text-zinc-400 line-through">{{ $item['unit_price_label'] }}</span>
                                @endif
                                <span class="text-xs text-zinc-400 md:hidden">{{ __('each') }}</span>
                            </div>

                            {{-- Quantity + (mobile) line total --}}
                            <div class="col-span-2 flex items-center justify-between gap-3 md:col-span-1 md:justify-center">
                                <div class="flex items-center rounded-full border border-zinc-300 bg-white shadow-sm">
                                    <button type="button" wire:click="decrease('{{ $item['key'] }}')" wire:loading.attr="disabled" aria-label="{{ __('Decrease quantity') }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-l-full text-zinc-600 transition hover:bg-zinc-50 hover:text-brand disabled:opacity-50">
                                        <x-storefront.icon name="minus" stroke="2.5" class="h-3.5 w-3.5" />
                                    </button>
                                    <span class="w-9 text-center text-sm font-bold tabular-nums text-sf-heading">{{ $item['quantity'] }}</span>
                                    <button type="button" wire:click="increase('{{ $item['key'] }}')" wire:loading.attr="disabled" aria-label="{{ __('Increase quantity') }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-r-full text-zinc-600 transition hover:bg-zinc-50 hover:text-brand disabled:opacity-50">
                                        <x-storefront.icon name="plus" stroke="2.5" class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                                <span class="text-base font-bold text-sf-heading md:hidden">{{ $item['line_total_label'] }}</span>
                            </div>

                            {{-- Line total (desktop) --}}
                            <span class="hidden text-right text-[15px] font-bold tabular-nums text-sf-heading md:block">{{ $item['line_total_label'] }}</span>

                            {{-- Remove (desktop) --}}
                            <button type="button" wire:click="remove('{{ $item['key'] }}')" aria-label="{{ __('Remove :name', ['name' => $item['name']]) }}" title="{{ __('Remove') }}"
                                class="hidden h-9 w-9 items-center justify-center justify-self-end rounded-full text-zinc-400 transition hover:bg-red-50 hover:text-red-600 md:flex">
                                <x-storefront.icon name="trash" stroke="1.8" class="h-4.5 w-4.5" />
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-100 bg-zinc-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('shop') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-zinc-600 transition hover:text-brand">
                        <x-storefront.icon name="arrow-left" />
                        {{ __('Continue shopping') }}
                    </a>
                    <button type="button" wire:click="clear" wire:confirm="{{ __('Remove every item from your cart?') }}"
                        class="inline-flex items-center gap-1.5 self-start rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-600 shadow-sm transition hover:border-red-300 hover:bg-red-50 hover:text-red-600 sm:self-auto">
                        <x-storefront.icon name="trash" stroke="1.8" />
                        {{ __('Clear cart') }}
                    </button>
                </div>
            </x-storefront.card>

            <x-storefront.card as="aside" :title="__('Order summary')" class="h-fit lg:sticky lg:top-24">
                <x-slot:actions><x-storefront.count-badge :count="$count" /></x-slot:actions>

                <dl class="space-y-3 px-5 py-5 text-sm">
                    <x-storefront.summary-row :label="__('Subtotal')">{{ format_money($subtotal) }}</x-storefront.summary-row>
                    @if ($vatEnabled && $vat > 0)
                        <x-storefront.summary-row :label="$vatLabel">{{ format_money($vat) }}</x-storefront.summary-row>
                    @endif
                    <x-storefront.summary-row :label="__('Shipping')" value-class="text-xs font-medium text-zinc-400">{{ __('Calculated at checkout') }}</x-storefront.summary-row>
                    <x-storefront.total-row>{{ format_money($total) }}</x-storefront.total-row>
                </dl>

                <div class="px-5 pb-5">
                    <x-storefront.button :href="route('checkout')" icon="bag">{{ __('Proceed to checkout') }}</x-storefront.button>
                </div>
            </x-storefront.card>
        </div>
    @endif
</main>
