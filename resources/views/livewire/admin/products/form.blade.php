<div class="max-w-[1600px] w-full mx-auto flex-1">

    <style>
        .jodit-fixed-wrap .jodit-container,
        .jodit-fixed-wrap .jodit-wysiwyg_wrap,
        .jodit-fixed-wrap .jodit-workplace,
        .jodit-fixed-wrap .jodit-wysiwyg {
            height: 180px !important;
            min-height: 180px !important;
            max-height: 180px !important;
            resize: none !important;
            overflow-y: auto !important;
        }

        .jodit-fixed-wrap .jodit-container {
            border-radius: 6px;
        }
    </style>

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.products') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 space-y-4">
        <div class="bg-white rounded-[5px] shadow-sm p-6">

            <x-admin-locale-tabs>
                @foreach (\App\Support\Locale::active() as $language)
                    <x-admin-locale-panel :code="$language->code" class="space-y-3">
                        <flux:field>
                            <flux:label>
                                Name
                                @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                            </flux:label>
                            <flux:input wire:model.live.debounce.400ms="name.{{ $language->code }}"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Product name' : 'Product name ('.($language->native_name ?: $language->name).')' }}" />
                            @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                        </flux:field>

                        <flux:field>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model.live.debounce.400ms="description.{{ $language->code }}" rows="4"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Short product summary' : 'Short product summary ('.($language->native_name ?: $language->name).')' }}" />
                            <p class="text-xs text-zinc-400 mt-1">Shown in listings and as a fallback description — the full page content is built separately in the page builder.</p>
                            @if ($language->code === $this->primaryLocale)<flux:error name="description.{{ $language->code }}" />@endif
                        </flux:field>
                    </x-admin-locale-panel>
                @endforeach

                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-name" />
                        @if ($slugAvailable === false)
                            <p class="text-xs text-red-500 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>This slug is already taken</p>
                        @elseif ($slugAvailable === true && $slug !== '')
                            <p class="text-xs text-green-600 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>This slug is available</p>
                        @else
                            <p class="text-xs text-zinc-400 mt-1">Auto-generated from the primary language's name as you type — edit it if you'd like a different one</p>
                        @endif
                        <flux:error name="slug" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Category</flux:label>
                        <flux:select wire:model="product_category_id">
                            <flux:select.option value="">— None —</flux:select.option>
                            @foreach ($this->productCategories as $cat)
                                <flux:select.option value="{{ $cat->id }}">
                                    {{ $cat->getTranslation('name', \App\Support\Locale::primary(), false) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="product_category_id" />
                    </flux:field>

                {{-- Pricing & Stock (not translatable — shown regardless of locale tab) --}}
                <div class="mt-5 rounded-xl border border-zinc-200 overflow-hidden" wire:key="pricing-stock-panel">
                    <div class="flex items-center gap-2.5 px-4 py-3 bg-emerald-50/60 border-b border-zinc-200">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600">
                            <flux:icon.banknotes class="size-4" />
                        </div>
                        <flux:heading size="sm">Pricing & Stock</flux:heading>
                    </div>

                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>Price</flux:label>
                                <flux:input type="number" wire:model.live.debounce.400ms="price" min="0" step="0.01" />
                                <flux:error name="price" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Discount Price</flux:label>
                                <flux:input type="number" wire:model.live.debounce.400ms="discount_price" min="0" step="0.01" placeholder="No discount" />
                                <flux:error name="discount_price" />
                            </flux:field>
                        </div>
                        @if ($discount_price !== '' && is_numeric($price) && is_numeric($discount_price) && (float) $discount_price < (float) $price && (float) $price > 0)
                            <p class="text-xs text-emerald-600 font-medium -mt-1">
                                {{ round((1 - ((float) $discount_price / (float) $price)) * 100) }}% off — shown as a strikethrough sale price.
                            </p>
                        @else
                            <p class="text-xs text-zinc-400 -mt-1">Leave Discount Price blank to sell at the regular price.</p>
                        @endif

                        <div class="border-t border-zinc-100 pt-4">
                            <div class="flex flex-wrap items-end gap-3">
                                <flux:field class="max-w-45">
                                    <flux:label>Quantity</flux:label>
                                    <flux:input type="number" wire:model.live.debounce.400ms="quantity" min="0" step="1" placeholder="Unlimited" />
                                    <flux:error name="quantity" />
                                </flux:field>
                                <div class="mb-1">
                                    @if ($quantity === '')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-500">
                                            Unlimited stock
                                        </span>
                                    @elseif ((int) $quantity > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            {{ (int) $quantity }} in stock
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-600 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Out of stock
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <p class="text-xs text-zinc-400 mt-1.5">Leave blank if stock isn't tracked for this product.</p>
                        </div>

                        <div class="border-t border-zinc-100 pt-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-zinc-700">Charge Shipping</p>
                                <p class="text-xs text-zinc-400">Turn off for digital products or items that always ship free.</p>
                            </div>
                            <flux:switch wire:model="charge_shipping" />
                        </div>
                    </div>
                </div>

            </x-admin-locale-tabs>
        </div>

        {{-- Variations --}}
        <x-admin-section-card icon="adjustments-horizontal" title="Variations" icon-color="bg-violet-500/10 text-violet-600"
            description="Optional attribute-based options (e.g. Size, Color) shown on the product page. Each value can override the base price — leave a value's price blank to keep the base price.">
            <x-slot:actions>
                @if ($variations)
                    <flux:button size="xs" variant="outline" icon="plus" wire:click="addVariationAttribute">Add attribute</flux:button>
                @endif
            </x-slot:actions>

            <div class="space-y-3">
                @forelse ($variations as $i => $attribute)
                    <div wire:key="variation-attr-{{ $i }}" class="group/attr rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-zinc-100 bg-linear-to-r from-violet-50/70 to-transparent">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-500/10 text-[12px] font-bold text-violet-600">
                                    {{ $i + 1 }}
                                </div>
                                <div class="min-w-0">
                                    <flux:heading size="sm">{{ $attribute['name'] ?: 'New attribute' }}</flux:heading>
                                    <p class="text-[11px] text-zinc-400 leading-tight">
                                        {{ count($attribute['values']) }} {{ Str::plural('value', count($attribute['values'])) }}
                                    </p>
                                </div>
                            </div>
                            <button type="button" wire:click="removeVariationAttribute({{ $i }})"
                                class="shrink-0 rounded-lg p-1.5 text-zinc-400 opacity-0 group-hover/attr:opacity-100 transition-all hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove attribute">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 space-y-4">
                            <flux:field class="max-w-xs">
                                <flux:label>Attribute Name <span class="text-red-500 ml-0.5">*</span></flux:label>
                                @if ($this->productAttributes->isEmpty())
                                    <p class="text-xs text-zinc-400">No attributes yet.
                                        <a href="{{ route('admin.product-attributes.create') }}" wire:navigate class="text-indigo-500 hover:underline">
                                            Create one
                                        </a>.
                                    </p>
                                @else
                                    <flux:select wire:model.live="variations.{{ $i }}.name">
                                        <flux:select.option value="">— Select —</flux:select.option>
                                        @foreach ($this->productAttributes as $attributeOption)
                                            <flux:select.option :value="$attributeOption->name">{{ $attributeOption->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @endif
                                <flux:error name="variations.{{ $i }}.name" />
                            </flux:field>

                            @php
                                $selectedAttribute = $this->productAttributes->firstWhere('name', $attribute['name']);
                                $availableValues = $selectedAttribute->values ?? [];
                            @endphp

                            <div class="rounded-lg bg-zinc-50/70 border border-zinc-100 p-3 space-y-2">
                                @if (! $attribute['name'])
                                    <p class="text-xs text-zinc-400 px-1 py-1">Select an attribute name first.</p>
                                @elseif (empty($availableValues))
                                    <p class="text-xs text-zinc-400 px-1 py-1">
                                        No values defined for "{{ $attribute['name'] }}" yet.
                                        @if ($selectedAttribute)
                                            <a href="{{ route('admin.product-attributes.edit', $selectedAttribute->id) }}" wire:navigate class="text-indigo-500 hover:underline">
                                                Add some
                                            </a>.
                                        @endif
                                    </p>
                                @else
                                    @forelse ($attribute['values'] as $j => $value)
                                    <div wire:key="variation-attr-{{ $i }}-value-{{ $j }}"
                                        class="group/val flex flex-wrap items-end gap-2 rounded-lg bg-white border border-zinc-200 p-2.5">
                                        <flux:field class="flex-1 min-w-35">
                                            <flux:label class="text-[11px] text-zinc-500">Value</flux:label>
                                            <flux:select wire:model="variations.{{ $i }}.values.{{ $j }}.name" size="sm">
                                                <flux:select.option value="">— Select —</flux:select.option>
                                                @foreach ($availableValues as $valueOption)
                                                    <flux:select.option :value="$valueOption">{{ $valueOption }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </flux:field>
                                        <flux:field class="w-28">
                                            <flux:label class="text-[11px] text-zinc-500">Price</flux:label>
                                            <flux:input type="number" step="0.01" min="0" size="sm"
                                                wire:model="variations.{{ $i }}.values.{{ $j }}.price" placeholder="Base price" />
                                            <flux:error name="variations.{{ $i }}.values.{{ $j }}.price" />
                                        </flux:field>
                                        <flux:field class="w-28">
                                            <flux:label class="text-[11px] text-zinc-500">Discount</flux:label>
                                            <flux:input type="number" step="0.01" min="0" size="sm"
                                                wire:model="variations.{{ $i }}.values.{{ $j }}.discount_price" placeholder="No discount" />
                                            <flux:error name="variations.{{ $i }}.values.{{ $j }}.discount_price" />
                                        </flux:field>
                                        <flux:field class="w-24">
                                            <flux:label class="text-[11px] text-zinc-500">Qty</flux:label>
                                            <flux:input type="number" step="1" min="0" size="sm"
                                                wire:model="variations.{{ $i }}.values.{{ $j }}.quantity" placeholder="Unlimited" />
                                            <flux:error name="variations.{{ $i }}.values.{{ $j }}.quantity" />
                                        </flux:field>
                                        <button type="button" wire:click="removeVariationValue({{ $i }}, {{ $j }})"
                                            class="shrink-0 mb-0.5 rounded-lg p-2 text-zinc-300 opacity-0 group-hover/val:opacity-100 transition-all hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove value">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-xs text-zinc-400 px-1 py-1">No values selected yet.</p>
                                @endforelse

                                    <button type="button" wire:click="addVariationValue({{ $i }})"
                                        class="flex items-center gap-1.5 text-xs font-medium text-violet-600 hover:text-violet-700 transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Add value
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-zinc-200 py-10 px-6 text-center">
                        <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-violet-500/10 text-violet-500">
                            <flux:icon.adjustments-horizontal class="size-5" />
                        </div>
                        <p class="text-sm font-medium text-zinc-600">No variations yet</p>
                        <p class="mt-1 text-xs text-zinc-400 max-w-sm mx-auto">Add an attribute like "Size" or "Color" if this product needs option-based pricing.</p>
                        <flux:button size="sm" variant="primary" icon="plus" class="mt-4" wire:click="addVariationAttribute">Add attribute</flux:button>
                    </div>
                @endforelse
            </div>
        </x-admin-section-card>

        @include('partials.admin-seo-fields')

        <div class="flex items-center gap-3 flex-wrap">
            <x-admin-save-button :label="$productId ? 'Update Product' : 'Create Product'" />
        </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-3">

            {{-- Featured Image --}}
            <x-admin-section-card icon="photo" title="Thumbnail Image" icon-color="bg-blue-500/10 text-blue-600"
                body-class="px-4 py-3" description="Shown in the product catalog. Recommended 800×800px.">
                <x-media-picker model="featured_image" label="" placeholder="Select featured image"
                    :picker-id="$featuredImagePickerId" mimes="jpg,jpeg,png,webp" only-images dropzone />
            </x-admin-section-card>

            {{-- Gallery --}}
            <x-admin-section-card icon="squares-2x2" title="Gallery" icon-color="bg-indigo-500/10 text-indigo-600"
                body-class="px-4 py-3" description="Extra product photos, shown on the product page. Drag to reorder.">
                <div
                    x-data="{
                        pickerId: @js($galleryPickerId),
                        init() {
                            const lKey = '__mpHandlerMulti_' + this.pickerId;
                            if (window[lKey]) window.removeEventListener('mediaPickerSelectedMultiple', window[lKey]);

                            window[lKey] = (ev) => {
                                const detail = Array.isArray(ev.detail) ? ev.detail[0] : ev.detail;
                                if (!detail || detail.pickerId !== this.pickerId || !detail.items) return;
                                $wire.addGalleryImages(detail.items.map((item) => item.id));
                            };
                            window.addEventListener('mediaPickerSelectedMultiple', window[lKey]);

                            if (typeof Sortable === 'undefined') return;
                            new Sortable(this.$refs.galleryGrid, {
                                animation: 150,
                                handle: '.gallery-drag-handle',
                                ghostClass: 'bg-indigo-50',
                                onEnd: () => {
                                    const ids = [...this.$refs.galleryGrid.querySelectorAll('[data-media-id]')]
                                        .map((el) => parseInt(el.dataset.mediaId));
                                    $wire.reorderGallery(ids);
                                },
                            });
                        },
                        openPicker() {
                            window.dispatchEvent(new CustomEvent('open-media-picker', {
                                detail: { pickerId: this.pickerId, onlyImages: true, mimes: 'jpg,jpeg,png,webp', maxSizeKb: 2048, multiple: true },
                            }));
                        },
                    }"
                    class="space-y-2"
                >
                    <div x-ref="galleryGrid" class="grid grid-cols-3 gap-2">
                        @foreach ($this->galleryMedia as $media)
                            <div wire:key="gallery-media-{{ $media->id }}" data-media-id="{{ $media->id }}"
                                class="gallery-drag-handle group relative aspect-square cursor-grab active:cursor-grabbing overflow-hidden rounded-lg border border-zinc-200">
                                <img src="{{ $media->url }}" alt="{{ $media->alt_text }}" class="h-full w-full object-cover" />
                                <button type="button" wire:click="removeGalleryImage({{ $media->id }})"
                                    class="absolute top-1 right-1 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-white shadow opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-50">
                                    <svg class="h-3 w-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" @click="openPicker()"
                        class="flex w-full items-center justify-center gap-1.5 rounded-lg border-2 border-dashed border-slate-300 py-2 text-xs font-semibold text-slate-500 transition-colors hover:border-indigo-400 hover:text-indigo-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Image
                    </button>
                </div>
            </x-admin-section-card>

            <livewire:admin.media-library.picker-modal key="products-form-picker-modal" />

        </div>

    </div>

</div>
