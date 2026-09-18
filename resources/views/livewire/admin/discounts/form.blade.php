<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.discounts') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full bg-white rounded-[5px] shadow-sm p-6 space-y-4">

        <flux:field>
            <flux:label>Name <span class="text-red-500 ml-0.5">*</span><x-field-hint text="Internal label, e.g. 'Winter Sale 20%'." /></flux:label>
            <flux:input wire:model="name" placeholder="e.g. Winter Sale 20% Off" />
            <flux:error name="name" />
        </flux:field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Discount Type <span class="text-red-500 ml-0.5">*</span></flux:label>
                <select wire:model="type"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount</option>
                </select>
                <flux:error name="type" />
            </flux:field>
            <flux:field>
                <flux:label>Value <span class="text-red-500 ml-0.5">*</span></flux:label>
                <flux:input type="number" step="0.01" min="0" wire:model="value"
                    placeholder="{{ $type === 'percentage' ? 'e.g. 20' : 'e.g. 500' }}" />
                <flux:error name="value" />
            </flux:field>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Valid From<x-field-hint text="Leave blank to always be valid." /></flux:label>
                <flux:input type="date" wire:model="starts_at" />
                <flux:error name="starts_at" />
            </flux:field>
            <flux:field>
                <flux:label>Valid Until<x-field-hint text="Leave blank for a discount that never expires." /></flux:label>
                <flux:input type="date" wire:model="ends_at" />
                <flux:error name="ends_at" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Applies To<x-field-hint text="Leave empty for this discount to apply to all products. Add one or more products to restrict it to just those." /></flux:label>

            <div
                x-data="{
                    productIds: @entangle('product_ids'),
                    allProducts: @js($this->products->map(fn ($product) => ['id' => $product->id, 'name' => $product->getTranslation('name', \App\Support\Locale::primary(), false)])),
                    query: '',
                    open: false,
                    panelStyle: '',
                    get filtered() {
                        const q = this.query.trim().toLowerCase();
                        return this.allProducts.filter(p => !this.productIds.includes(p.id) && (!q || p.name.toLowerCase().includes(q)));
                    },
                    get selected() {
                        return this.productIds.map(id => this.allProducts.find(p => p.id === id)).filter(Boolean);
                    },
                    updatePosition() {
                        this.$nextTick(() => {
                            const trigger = this.$refs.productBox;
                            if (!trigger) return;
                            const rect = trigger.getBoundingClientRect();
                            this.panelStyle = `top:${rect.bottom + 4}px; left:${rect.left}px; width:${rect.width}px;`;
                        });
                    },
                    openDropdown() {
                        this.open = true;
                        this.updatePosition();
                    },
                    addProduct(id) {
                        if (!this.productIds.includes(id)) this.productIds.push(id);
                        this.query = '';
                        this.$refs.productSearch.focus();
                    },
                    removeProduct(id) {
                        this.productIds = this.productIds.filter(existing => existing !== id);
                    },
                    init() {
                        const handler = (e) => {
                            if (!this.$refs.productSearch || !document.body.contains(this.$refs.productSearch)) {
                                document.removeEventListener('click', handler);
                                return;
                            }
                            if (!this.open) return;
                            if (this.$refs.productBox && this.$refs.productBox.contains(e.target)) return;
                            if (e.target.closest('[data-discount-product-panel]')) return;
                            this.open = false;
                        };
                        document.addEventListener('click', handler);
                        window.addEventListener('resize', () => this.open && this.updatePosition());
                        window.addEventListener('scroll', () => this.open && this.updatePosition(), true);
                    },
                }"
                class="relative"
            >
                <div x-ref="productBox" @click="$refs.productSearch.focus()"
                    class="flex flex-wrap items-center gap-1.5 min-h-9 w-full rounded-lg border border-zinc-200 px-2 py-1.5 cursor-text focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100 transition-all">
                    <template x-for="product in selected" :key="product.id">
                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 pl-2.5 pr-1.5 py-1 text-xs font-medium text-indigo-700">
                            <span x-text="product.name"></span>
                            <button type="button" @click.stop="removeProduct(product.id)"
                                class="rounded-full p-0.5 hover:bg-indigo-100 transition-colors">
                                <flux:icon name="x-mark" variant="micro" class="size-3" />
                            </button>
                        </span>
                    </template>

                    <input type="text" x-ref="productSearch" x-model="query" @focus="openDropdown()" @input="open = true; updatePosition()"
                        placeholder="Search products…"
                        class="flex-1 min-w-25 border-0 p-0.5 text-sm outline-none focus:ring-0" />
                </div>

                <template x-teleport="body">
                    <div data-discount-product-panel x-show="open && filtered.length" x-cloak x-transition :style="panelStyle"
                        class="fixed z-50 max-h-56 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg py-1">
                        <template x-for="product in filtered" :key="product.id">
                            <button type="button" @click="addProduct(product.id)"
                                class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50 transition-colors"
                                x-text="product.name"></button>
                        </template>
                    </div>
                </template>

                <p x-show="open && query && !filtered.length" x-cloak class="mt-1 text-xs text-zinc-400">
                    No matching products.
                </p>

                @if ($this->products->isEmpty())
                    <p class="mt-1 text-xs text-zinc-400">No products yet.</p>
                @endif
            </div>
            <flux:error name="product_ids" />
        </flux:field>

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="admin-btn-save inline-flex items-center gap-2 px-5 h-8 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                <svg wire:loading.remove wire:target="save" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17 21 17 13 7 13 7 21" />
                    <polyline points="7 3 7 8 15 8" />
                </svg>
                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" stroke-opacity="0.25" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke-opacity="1" />
                </svg>
                <span wire:loading.remove wire:target="save">{{ $discountId ? 'Update Discount' : 'Create Discount' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>

    </div>
</div>