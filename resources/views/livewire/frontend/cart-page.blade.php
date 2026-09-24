@php
    $steps = [
        ['label' => __('Cart'), 'state' => 'current'],
        ['label' => __('Checkout'), 'state' => 'upcoming'],
        ['label' => __('Order placed'), 'state' => 'upcoming'],
    ];
@endphp

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-12">
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-sf-heading md:text-3xl">{{ __('My cart') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Review your items before checkout.') }}</p>
        </div>

        {{-- Progress: where the shopper is in the purchase flow. --}}
        <ol class="flex items-center gap-2 text-xs font-semibold sm:gap-3">
            @foreach ($steps as $i => $step)
                <li class="flex items-center gap-2">
                    <span @class([
                        'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                        'bg-brand text-white shadow-sm' => $step['state'] === 'current',
                        'border border-zinc-300 bg-white text-zinc-400' => $step['state'] === 'upcoming',
                    ])>{{ $i + 1 }}</span>
                    <span class="{{ $step['state'] === 'current' ? 'text-sf-heading' : 'text-zinc-400' }}">{{ $step['label'] }}</span>
                    @unless ($loop->last)
                        <span class="h-px w-6 bg-zinc-300 sm:w-10"></span>
                    @endunless
                </li>
            @endforeach
        </ol>
    </div>

    @if ($count === 0)
        <div class="mx-auto flex max-w-md flex-col items-center rounded-card border border-dashed border-zinc-300 bg-white px-6 py-16 text-center shadow-sm">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand/10 text-brand">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
            </span>
            <h2 class="mt-5 text-lg font-bold text-sf-heading">{{ __('Your cart is empty') }}</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Add some products from the shop to get started.') }}</p>
            <a href="{{ route('shop') }}"
                class="mt-6 inline-flex items-center gap-2 rounded-full bg-sf-button px-6 py-2.5 text-sm font-semibold text-sf-button-text shadow-sm transition hover:opacity-90">
                {{ __('Browse products') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_22rem] lg:items-start">
            <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                <header class="flex items-center justify-between gap-3 border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                    <h2 class="text-base font-bold text-sf-heading">{{ __('Cart items') }}</h2>
                    <span class="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-bold text-brand">{{ trans_choice(':count item|:count items', $count, ['count' => $count]) }}</span>
                </header>

                {{-- Column headings (desktop only — rows stack on small screens). --}}
                <div class="hidden grid-cols-[1fr_7rem_8rem_7rem_2.5rem] items-center gap-4 border-b border-zinc-100 bg-zinc-50/60 px-5 py-3 text-xs font-bold uppercase tracking-wide text-zinc-500 md:grid">
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
                                <a href="{{ $item['url'] }}" class="row-span-2 block h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-zinc-100 bg-zinc-50 md:row-span-1">
                                    @if ($item['image'])
                                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover transition duration-300 hover:scale-105">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-zinc-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                                            </svg>
                                        </div>
                                    @endif
                                </a>

                                <div class="min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <a href="{{ $item['url'] }}" class="line-clamp-2 text-[15px] font-semibold text-sf-text transition-colors hover:text-brand">
                                            {{ $item['name'] }}
                                        </a>
                                        {{-- Mobile remove --}}
                                        <button type="button" wire:click="remove('{{ $item['key'] }}')" aria-label="{{ __('Remove :name', ['name' => $item['name']]) }}"
                                            class="-mr-1.5 -mt-1 shrink-0 rounded-full p-1.5 text-zinc-400 transition hover:bg-red-50 hover:text-red-600 md:hidden">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                    @if ($item['options_label'])
                                        <p class="mt-1 inline-flex rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600">{{ $item['options_label'] }}</p>
                                    @endif
                                    @if ($item['discount_label'])
                                        <p class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 md:flex">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                            </svg>
                                            {{ __('On sale') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Price --}}
                            <div class="col-start-2 flex items-baseline gap-2 text-sm md:col-start-auto md:flex-col md:items-end md:gap-0">
                                @if ($item['discount_label'])
                                    <span class="font-semibold text-sf-heading">{{ $item['discount_label'] }}</span>
                                    <span class="text-xs text-zinc-400 line-through">{{ $item['unit_price_label'] }}</span>
                                @else
                                    <span class="font-semibold text-sf-heading">{{ $item['unit_price_label'] }}</span>
                                @endif
                                <span class="text-xs text-zinc-400 md:hidden">{{ __('each') }}</span>
                            </div>

                            {{-- Quantity + (mobile) line total --}}
                            <div class="col-span-2 flex items-center justify-between gap-3 md:col-span-1 md:justify-center">
                                <div class="flex items-center rounded-full border border-zinc-300 bg-white shadow-sm">
                                    <button type="button" wire:click="decrease('{{ $item['key'] }}')" wire:loading.attr="disabled" aria-label="{{ __('Decrease quantity') }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-l-full text-zinc-600 transition hover:bg-zinc-50 hover:text-brand disabled:opacity-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                        </svg>
                                    </button>
                                    <span class="w-9 text-center text-sm font-bold tabular-nums text-sf-heading">{{ $item['quantity'] }}</span>
                                    <button type="button" wire:click="increase('{{ $item['key'] }}')" wire:loading.attr="disabled" aria-label="{{ __('Increase quantity') }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-r-full text-zinc-600 transition hover:bg-zinc-50 hover:text-brand disabled:opacity-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14" />
                                        </svg>
                                    </button>
                                </div>
                                <span class="text-base font-bold text-sf-heading md:hidden">{{ $item['line_total_label'] }}</span>
                            </div>

                            {{-- Line total (desktop) --}}
                            <span class="hidden text-right text-[15px] font-bold tabular-nums text-sf-heading md:block">{{ $item['line_total_label'] }}</span>

                            {{-- Remove (desktop) --}}
                            <button type="button" wire:click="remove('{{ $item['key'] }}')" aria-label="{{ __('Remove :name', ['name' => $item['name']]) }}" title="{{ __('Remove') }}"
                                class="hidden h-9 w-9 items-center justify-center justify-self-end rounded-full text-zinc-400 transition hover:bg-red-50 hover:text-red-600 md:flex">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-100 bg-zinc-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('shop') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-zinc-600 transition hover:text-brand">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        {{ __('Continue shopping') }}
                    </a>
                    <button type="button" wire:click="clear" wire:confirm="{{ __('Remove every item from your cart?') }}"
                        class="inline-flex items-center gap-1.5 self-start rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-600 shadow-sm transition hover:border-red-300 hover:bg-red-50 hover:text-red-600 sm:self-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        {{ __('Clear cart') }}
                    </button>
                </div>
            </section>

            <aside class="h-fit overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm lg:sticky lg:top-24">
                <div class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                    <h2 class="text-base font-bold text-sf-heading">{{ __('Order summary') }}</h2>
                    <span class="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-bold text-brand">{{ trans_choice(':count item|:count items', $count, ['count' => $count]) }}</span>
                </div>

                <dl class="space-y-3 px-5 py-5 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500">{{ __('Subtotal') }}</dt>
                        <dd class="font-semibold tabular-nums text-sf-heading">{{ format_money($subtotal) }}</dd>
                    </div>
                    @if ($vatEnabled && $vat > 0)
                        <div class="flex items-center justify-between">
                            <dt class="text-zinc-500">{{ $vatLabel }}</dt>
                            <dd class="font-semibold tabular-nums text-sf-heading">{{ format_money($vat) }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500">{{ __('Shipping') }}</dt>
                        <dd class="text-xs font-medium text-zinc-400">{{ __('Calculated at checkout') }}</dd>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-brand/5 px-3.5 py-3">
                        <dt class="text-base font-bold text-sf-heading">{{ __('Total') }}</dt>
                        <dd class="text-xl font-extrabold tabular-nums text-sf-price">{{ format_money($total) }}</dd>
                    </div>
                </dl>

                <div class="px-5 pb-5">
                    <a href="{{ route('checkout') }}"
                        class="flex w-full items-center justify-center gap-2 rounded-[5px] bg-sf-button px-6 py-4 text-sm font-bold text-sf-button-text shadow-md shadow-brand/20 transition hover:opacity-90">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span>{{ __('Proceed to checkout') }}</span>
                    </a>
                </div>
            </aside>
        </div>
    @endif
</main>
