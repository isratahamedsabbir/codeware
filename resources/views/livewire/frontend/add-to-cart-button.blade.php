@assets
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productVariants', (data) => ({
            groups: data.groups,
            variations: data.variations,
            // First visible combination starts pre-selected, so the price and
            // stock shown before any interaction match an actual purchasable
            // option — the "Select options to see stock." placeholder never
            // flashes when a product carries variations.
            selection: (data.initial_selection && { ...data.initial_selection }) || {},
            base_price_label: data.base_price_label,
            base_discount_label: data.base_discount_label,
            base_stock_label: data.base_stock_label,

            toggle(attribute, value) {
                if (this.selection[attribute] === value) {
                    delete this.selection[attribute];
                } else {
                    this.selection[attribute] = value;
                }
            },

            // A value button is disabled when, given the current selection, no
            // combination that includes it is actually in stock — the variant
            // stays visible but can't be bought.
            canPick(attribute, value) {
                const prospective = { ...this.selection, [attribute]: value };
                const matches = this.variations.filter((variation) =>
                    Object.keys(prospective).every((name) =>
                        prospective[name] !== undefined && variation.attributes[name] === prospective[name]
                    )
                );

                return matches.length > 0 && matches.some((variation) => variation.in_stock);
            },

            get pickedCount() {
                return Object.keys(this.selection).filter((name) => this.selection[name] !== undefined).length;
            },

            get ready() {
                return this.pickedCount === Object.keys(this.groups).length;
            },

            get selected() {
                const names = Object.keys(this.selection).filter((name) => this.selection[name] !== undefined);
                const namesCount = names.length;

                if (!namesCount) {
                    return null;
                }

                return this.variations.find((variation) => {
                    const attrs = variation.attributes;
                    const attrNames = Object.keys(attrs);

                    return namesCount === attrNames.length
                        && names.every((name) => this.selection[name] === attrs[name]);
                }) ?? null;
            },

            async addToCart() {
                if (!this.ready || !this.selected || !this.selected.in_stock) {
                    return;
                }

                await $wire.call('add', this.selection);

                // Only a *successful* add (verified via the re-rendered flag,
                // not just the round-trip) lets the card modal close itself.
                if ($wire.get('added')) {
                    window.dispatchEvent(new CustomEvent('variant-added'));
                }
            },
        }));
    });
</script>
@endassets

@php
    $variationGroups = [];
    $variationRows = [];
    $pickerBaseDiscount = null;

    if ($product !== null) {
        foreach ($variations as $row) {
            foreach (($row['attributes'] ?? []) as $attribute => $value) {
                $variationGroups[$attribute] = array_values(array_unique(array_merge($variationGroups[$attribute] ?? [], [$value])));
            }
        }

        $variationRows = collect($variations)->map(fn (array $row) => [
            'attributes' => $row['attributes'] ?? [],
            'sku' => ($row['sku'] ?? null) ?: null,
            'price_label' => format_money($row['price'] ?? $product->price),
            'discount_label' => (isset($row['price'], $row['discount_price']) && (float) $row['discount_price'] < (float) $row['price'])
                ? format_money($row['discount_price'])
                : null,
            'in_stock' => (int) ($row['quantity'] ?? 0) > 0,
            'stock_label' => (int) ($row['quantity'] ?? 0) > 0
                ? __('In stock')
                : __('Out of stock'),
        ])->values()->all();

        $pickerBaseDiscount = $product->hasDiscount() ? format_money($product->discount_price) : null;
    }

    $pickerWireData = \Illuminate\Support\Js::from([
        'groups' => $variationGroups,
        'variations' => $variationRows,
        'initial_selection' => $variations[0]['attributes'] ?? null,
        'base_price_label' => $product !== null ? format_money($product->price) : null,
        'base_discount_label' => $pickerBaseDiscount,
        'base_stock_label' => ($product !== null && $product->inStock()) ? __('In stock') : __('Out of stock'),
    ]);
@endphp

<div class="w-full">
    @if ($isUpcoming)
        <button
            type="button"
            disabled
            class="w-full cursor-not-allowed rounded-full bg-zinc-100 px-4 py-2.5 text-center text-sm font-semibold text-zinc-400"
        >{{ __('Coming soon') }}</button>
    @elseif ($requiresOptions && $hasVisibleVariations)
        @if ($product)
            <div x-data="{ pickerOpen: false }">
                <button
                    type="button"
                    @click="pickerOpen = true"
                    aria-live="polite"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    @if ($added)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>{{ __('Added') }}</span>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <span>{{ __('Add to cart') }}</span>
                    @endif
                </button>

                {{-- Options popup on the card: pick a combination and add it
                    straight to the cart without leaving the grid. --}}
                <div
                    x-show="pickerOpen"
                    x-transition.opacity
                    @cart-item-added.window="pickerOpen = false"
                    @keydown.escape.window="pickerOpen = false"
                    role="dialog"
                    aria-modal="true"
                    class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4"
                >
                    <div
                        class="relative w-full max-w-md rounded-t-2xl bg-white p-6 shadow-2xl sm:rounded-2xl"
                        @click.outside="pickerOpen = false"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="line-clamp-2 text-base font-bold text-zinc-900">{{ $product->name }}</h3>
                            <button type="button" @click="pickerOpen = false" aria-label="{{ __('Close') }}"
                                class="shrink-0 rounded-full p-1.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-2 max-h-[min(70vh,32rem)] overflow-y-auto pr-1">
                            <div x-data="productVariants({!! $pickerWireData !!})" class="w-full">
                                <template x-for="(values, attribute) in groups" :key="attribute">
                                    <div class="mt-4">
                                        <h3 class="mb-2 text-sm font-semibold text-zinc-900" x-text="attribute"></h3>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="value in values" :key="value">
                                                <button type="button" @click="toggle(attribute, value)"
                                                    :disabled="!canPick(attribute, value)"
                                                    :class="!canPick(attribute, value)
                                                        ? 'cursor-not-allowed border-zinc-200 text-zinc-300 line-through'
                                                        : (selection[attribute] === value
                                                            ? 'border-primary bg-primary/10 text-primary'
                                                            : 'border-zinc-200 text-zinc-700 hover:border-primary/50')"
                                                    class="rounded-full border px-4 py-2 text-sm font-medium transition"
                                                    x-text="value"></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <div class="mt-6 flex items-baseline gap-3">
                                    <template x-if="selected">
                                        <div class="flex flex-wrap items-baseline gap-3">
                                            <span class="text-2xl font-extrabold text-zinc-900" x-text="selected.discount_label || selected.price_label"></span>
                                            <span x-show="selected.discount_label" class="text-base text-zinc-400 line-through" x-text="selected.price_label"></span>
                                            <span class="mt-1 w-full text-sm" :class="selected.in_stock ? 'text-emerald-600' : 'text-red-500'" x-text="selected.stock_label"></span>
                                            <template x-if="selected.sku">
                                                <span class="mt-1 w-full text-xs text-zinc-400">{{ __('SKU') }}: <span class="font-mono" x-text="selected.sku"></span></span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!selected">
                                        <div class="flex flex-wrap items-baseline gap-3">
                                            <span class="text-2xl font-extrabold text-zinc-900" x-text="base_discount_label || base_price_label"></span>
                                            <span x-show="base_discount_label" class="text-base text-zinc-400 line-through" x-text="base_price_label"></span>
                                            <span class="mt-1 w-full text-sm text-zinc-500">{{ __('Select options to see stock.') }}</span>
                                        </div>
                                    </template>
                                </div>

                                <div class="mt-6 flex w-full items-center gap-2">
                                    @if ($adjustable)
                                        <div class="flex shrink-0 items-center rounded-full border border-zinc-200 bg-white">
                                            <button type="button" wire:click="decrease" aria-label="{{ __('Decrease quantity') }}"
                                                class="flex h-11 w-9 items-center justify-center rounded-l-full text-lg font-semibold text-zinc-600 transition hover:text-brand">
                                                &minus;
                                            </button>
                                            <span class="w-10 text-center text-sm font-bold text-zinc-900">{{ $quantity }}</span>
                                            <button type="button" wire:click="increase" aria-label="{{ __('Increase quantity') }}"
                                                class="flex h-11 w-9 items-center justify-center rounded-r-full text-lg font-semibold text-zinc-600 transition hover:text-brand">
                                                +
                                            </button>
                                        </div>
                                    @endif

                                    <button
                                        type="button"
                                        @click="addToCart()"
                                        :disabled="!ready || (selected && !selected.in_stock)"
                                        :class="ready && selected && selected.in_stock ? 'bg-brand' : 'cursor-not-allowed bg-zinc-300'"
                                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                        </svg>
                                        <span>{{ __('Add to cart') }}</span>
                                    </button>
                                </div>

                                @error('options')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <a
                href="{{ $slug ? route('products.show', $slug) : '#' }}"
                class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-zinc-200 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:border-brand hover:text-brand"
            >{{ __('Select options') }}</a>
        @endif
    @elseif ($showPicker && $hasVisibleVariations && $product)
        <div
            x-data="productVariants({!! $pickerWireData !!})"
            class="w-full"
        >
            <template x-for="(values, attribute) in groups" :key="attribute">
                <div class="mt-4">
                    <h3 class="mb-2 text-sm font-semibold text-zinc-900" x-text="attribute"></h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="value in values" :key="value">
                            <button type="button" @click="toggle(attribute, value)"
                                :disabled="!canPick(attribute, value)"
                                :class="!canPick(attribute, value)
                                    ? 'cursor-not-allowed border-zinc-200 text-zinc-300 line-through'
                                    : (selection[attribute] === value
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-zinc-200 text-zinc-700 hover:border-primary/50')"
                                class="rounded-full border px-4 py-2 text-sm font-medium transition"
                                x-text="value"></button>
                        </template>
                    </div>
                </div>
            </template>

            <div class="mt-6 flex items-baseline gap-3">
                <template x-if="selected">
                    <div class="flex flex-wrap items-baseline gap-3">
                        <span class="text-3xl font-extrabold text-zinc-900" x-text="selected.discount_label || selected.price_label"></span>
                        <span x-show="selected.discount_label" class="text-lg text-zinc-400 line-through" x-text="selected.price_label"></span>
                        <span class="mt-1 w-full text-sm" :class="selected.in_stock ? 'text-emerald-600' : 'text-red-500'" x-text="selected.stock_label"></span>
                        <template x-if="selected.sku">
                            <span class="mt-1 w-full text-xs text-zinc-400">{{ __('SKU') }}: <span class="font-mono" x-text="selected.sku"></span></span>
                        </template>
                    </div>
                </template>
                <template x-if="!selected">
                    <div class="flex flex-wrap items-baseline gap-3">
                        <span class="text-3xl font-extrabold text-zinc-900" x-text="base_discount_label || base_price_label"></span>
                        <span x-show="base_discount_label" class="text-lg text-zinc-400 line-through" x-text="base_price_label"></span>
                        @if (! $product->is_upcoming)
                            <span class="mt-1 w-full text-sm text-zinc-500">{{ __('Select options to see stock.') }}</span>
                        @endif
                    </div>
                </template>
            </div>

            <div class="mt-6 flex w-full items-center gap-2">
                @if ($adjustable)
                    <div class="flex shrink-0 items-center rounded-full border border-zinc-200 bg-white">
                        <button type="button" wire:click="decrease" aria-label="{{ __('Decrease quantity') }}"
                            class="flex h-11 w-9 items-center justify-center rounded-l-full text-lg font-semibold text-zinc-600 transition hover:text-brand">
                            &minus;
                        </button>
                        <span class="w-10 text-center text-sm font-bold text-zinc-900">{{ $quantity }}</span>
                        <button type="button" wire:click="increase" aria-label="{{ __('Increase quantity') }}"
                            class="flex h-11 w-9 items-center justify-center rounded-r-full text-lg font-semibold text-zinc-600 transition hover:text-brand">
                            +
                        </button>
                    </div>
                @endif

                <button
                    type="button"
                    @click="addToCart()"
                    :disabled="!ready || (selected && !selected.in_stock)"
                    :class="ready && selected && selected.in_stock ? ({{ $added ? "'bg-emerald-600'" : "'bg-brand'" }}) : 'cursor-not-allowed bg-zinc-300'"
                    aria-live="polite"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    @if ($added)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>{{ __('Added') }}</span>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <span>{{ __('Add to cart') }}</span>
                    @endif
                </button>
            </div>

            @error('options')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>
    @elseif (! $inStock)
        <button
            type="button"
            disabled
            class="w-full cursor-not-allowed rounded-full bg-zinc-100 px-4 py-2.5 text-center text-sm font-semibold text-zinc-400"
        >{{ __('Out of stock') }}</button>
    @else
        <div class="flex w-full items-center gap-2">
            @if ($adjustable)
                <div class="flex shrink-0 items-center rounded-full border border-zinc-200 bg-white">
                    <button type="button" wire:click="decrease" aria-label="{{ __('Decrease quantity') }}"
                        class="flex h-11 w-9 items-center justify-center rounded-l-full text-lg font-semibold text-zinc-600 transition hover:text-brand">
                        &minus;
                    </button>
                    <span class="w-10 text-center text-sm font-bold text-zinc-900">{{ $quantity }}</span>
                    <button type="button" wire:click="increase" aria-label="{{ __('Increase quantity') }}"
                        class="flex h-11 w-9 items-center justify-center rounded-r-full text-lg font-semibold text-zinc-600 transition hover:text-brand">
                        +
                    </button>
                </div>
            @endif

            <button
                type="button"
                wire:click="add"
                aria-live="polite"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 {{ $added ? 'bg-emerald-600' : 'bg-brand' }}"
            >
                @if ($added)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span>{{ __('Added') }}</span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    <span>{{ __('Add to cart') }}</span>
                @endif
            </button>
        </div>
    @endif
</div>