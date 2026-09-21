<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold uppercase tracking-wide text-zinc-800 md:text-3xl">{{ __('My cart') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('Review your items before checkout.') }}</p>
    </div>

    @if ($count === 0)
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-20 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-14 w-14 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>
            <h2 class="mt-4 text-lg font-semibold text-zinc-800">{{ __('Your cart is empty') }}</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-zinc-500">
                {{ __('Add some products from the shop to get started.') }}
            </p>
            <a href="{{ route('shop') }}"
                class="mt-6 inline-block rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                {{ __('Browse products') }}
            </a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
            <div class="space-y-4">
                @foreach ($items as $item)
                    <div class="flex gap-4 rounded-2xl border border-zinc-100 bg-white p-4 shadow-sm">
                        <a href="{{ $item['url'] }}" class="block h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-zinc-100">
                            @if ($item['image'])
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-zinc-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                                    </svg>
                                </div>
                            @endif
                        </a>

                        <div class="flex min-w-0 flex-1 flex-col">
                            <div class="flex items-start justify-between gap-3">
                                <a href="{{ $item['url'] }}" class="line-clamp-2 text-[15px] font-semibold text-zinc-800 transition-colors hover:text-brand">
                                    {{ $item['name'] }}
                                </a>
                                <button type="button" wire:click="remove('{{ $item['key'] }}')" aria-label="{{ __('Remove :name', ['name' => $item['name']]) }}"
                                    class="shrink-0 rounded-full p-1.5 text-zinc-400 transition hover:bg-red-50 hover:text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            @if ($item['options_label'])
                                <p class="mt-1 text-xs font-medium text-zinc-500">{{ $item['options_label'] }}</p>
                            @endif

                            <div class="mt-1 flex items-baseline gap-2 text-sm">
                                @if ($item['discount_label'])
                                    <span class="font-semibold text-brand">{{ $item['discount_label'] }}</span>
                                    <span class="text-zinc-400 line-through">{{ $item['unit_price_label'] }}</span>
                                @else
                                    <span class="text-zinc-600">{{ __(':price each', ['price' => $item['unit_price_label']]) }}</span>
                                @endif
                            </div>

                            <div class="mt-auto flex items-center justify-between gap-3 pt-3">
                                <div class="flex items-center rounded-full border border-zinc-200">
                                    <button type="button" wire:click="decrease('{{ $item['key'] }}')" aria-label="{{ __('Decrease quantity') }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-l-full text-zinc-600 transition hover:text-brand">
                                        &minus;
                                    </button>
                                    <span class="w-8 text-center text-sm font-bold text-zinc-900">{{ $item['quantity'] }}</span>
                                    <button type="button" wire:click="increase('{{ $item['key'] }}')" aria-label="{{ __('Increase quantity') }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-r-full text-zinc-600 transition hover:text-brand">
                                        +
                                    </button>
                                </div>
                                <span class="text-[15px] font-bold text-zinc-900">{{ $item['line_total_label'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach

                <button type="button" wire:click="clear"
                    class="text-sm font-semibold text-zinc-500 underline-offset-4 transition hover:text-red-600 hover:underline">
                    {{ __('Clear cart') }}
                </button>
            </div>

            <aside class="h-fit rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                <h2 class="text-base font-bold text-zinc-900">{{ __('Order summary') }}</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500">{{ __(':count items', ['count' => $count]) }}</dt>
                        <dd class="font-semibold text-zinc-900">{{ format_money($subtotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-zinc-100 pt-3">
                        <dt class="text-base font-bold text-zinc-900">{{ __('Total') }}</dt>
                        <dd class="text-lg font-extrabold text-zinc-900">{{ format_money($subtotal) }}</dd>
                    </div>
                </dl>

                <a href="{{ route('checkout') }}"
                    class="mt-5 flex w-full items-center justify-center gap-2 rounded-full bg-brand px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Proceed to checkout') }}
                </a>
                <a href="{{ route('shop') }}"
                    class="mt-3 block text-center text-sm font-semibold text-zinc-600 underline-offset-4 transition hover:text-brand hover:underline">
                    {{ __('Continue shopping') }}
                </a>
            </aside>
        </div>
    @endif
</main>