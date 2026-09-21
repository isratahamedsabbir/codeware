@assets
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productVariants', (data) => ({
            groups: data.groups,
            variations: data.variations,
            selection: {},
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

            addToCart() {
                if (!this.ready || !this.selected || !this.selected.in_stock) {
                    return;
                }

                $wire.call('add', this.selection);
            },
        }));
    });
</script>
@endassets

<div class="w-full">
    @if ($isUpcoming)
        <button
            type="button"
            disabled
            class="w-full cursor-not-allowed rounded-full bg-zinc-100 px-4 py-2.5 text-center text-sm font-semibold text-zinc-400"
        >{{ __('Coming soon') }}</button>
    @elseif ($requiresOptions && $hasVisibleVariations)
        <a
            href="{{ $slug ? route('products.show', $slug) : '#' }}"
            class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-zinc-200 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:border-brand hover:text-brand"
        >{{ __('Select options') }}</a>
    @elseif ($showPicker && $hasVisibleVariations && $product)
        @php
            $variationGroups = [];
            foreach ($variations as $row) {
                foreach (($row['attributes'] ?? []) as $attribute => $value) {
                    $variationGroups[$attribute] = array_values(array_unique(array_merge($variationGroups[$attribute] ?? [], [$value])));
                }
            }

            $variationRows = collect($variations)->map(fn (array $row) => [
                'attributes' => $row['attributes'] ?? [],
                'price_label' => format_money($row['price'] ?? $product->price),
                'discount_label' => (isset($row['price'], $row['discount_price']) && (float) $row['discount_price'] < (float) $row['price'])
                    ? format_money($row['discount_price'])
                    : null,
                'stock_label' => (($row['quantity'] ?? null) === null || (int) $row['quantity'] > 0)
                    ? __('In stock')
                    : __('Out of stock'),
                'in_stock' => ($row['quantity'] ?? null) === null ? $product->inStock() : (int) $row['quantity'] > 0,
            ])->values()->all();

            $pickerBaseDiscount = $product->hasDiscount() ? format_money($product->discount_price) : null;
        @endphp

        <div
            x-data="productVariants(@js([
                'groups' => $variationGroups,
                'variations' => $variationRows,
                'base_price_label' => format_money($product->price),
                'base_discount_label' => $pickerBaseDiscount,
                'base_stock_label' => $product->inStock() ? __('In stock') : __('Out of stock'),
            ]))"
            class="w-full"
        >
            <template x-for="(values, attribute) in groups" :key="attribute">
                <div class="mt-4">
                    <h3 class="mb-2 text-sm font-semibold text-zinc-900" x-text="attribute"></h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="value in values" :key="value">
                            <button type="button" @click="toggle(attribute, value)"
                                :class="selection[attribute] === value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-zinc-200 text-zinc-700 hover:border-primary/50'"
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