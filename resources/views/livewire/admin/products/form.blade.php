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
                            <flux:label>Description<x-field-hint text="Shown in listings and as a fallback description — the full page content is built separately in the page builder." /></flux:label>
                            <flux:textarea wire:model.live.debounce.400ms="description.{{ $language->code }}" rows="4"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Short product summary' : 'Short product summary ('.($language->native_name ?: $language->name).')' }}" />
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

                {{-- Product Type (not translatable — shown regardless of locale tab) --}}
                <div class="mt-4" wire:key="product-type-panel">
                    <flux:field>
                        <flux:label>Product Type</flux:label>
                        <div class="grid grid-cols-2 gap-1.5 rounded-lg bg-zinc-100 p-1 max-w-xs">
                            <button type="button" wire:click="setProductType('physical')"
                                class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ $product_type === 'physical' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                <flux:icon.cube class="h-3.5 w-3.5 shrink-0" />
                                Physical
                            </button>
                            <button type="button" wire:click="setProductType('digital')"
                                class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ $product_type === 'digital' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                <flux:icon.arrow-down-tray class="h-3.5 w-3.5 shrink-0" />
                                Digital
                            </button>
                        </div>
                        <flux:error name="product_type" />
                    </flux:field>
                </div>

                {{-- Pricing & Stock (not translatable — shown regardless of locale tab) --}}
                <div class="mt-4" wire:key="pricing-stock-panel">
                    <flux:heading size="sm" class="mb-3">Pricing & Stock</flux:heading>

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

                    <div class="mt-4">
                        <flux:field>
                            <flux:label>
                                Quantity<x-field-hint text="Leave blank to mark this product out of stock." />
                                <x-slot:trailing>
                                    @if ($quantity !== '' && (int) $quantity > 0)
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
                                </x-slot:trailing>
                            </flux:label>
                            <flux:input type="number" wire:model.live.debounce.400ms="quantity" min="0" step="1" placeholder="0" />
                            <flux:error name="quantity" />
                        </flux:field>
                    </div>
                </div>

            </x-admin-locale-tabs>
        </div>

        {{-- Variations --}}
        <x-admin-section-card icon="adjustments-horizontal" title="Variations" icon-color="bg-violet-500/10 text-violet-600"
            description="Pick which attributes apply to this product, then check the values that matter (e.g. Color: Red, Blue + Size: Small) — a card for every combination appears automatically, each optionally overriding the base price/stock."
            collapsible :collapsed="true">

            {{-- Which attributes apply to this product --}}
            <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-4 mb-3">
                @if ($this->productAttributes->isEmpty())
                    <p class="text-xs text-zinc-400">No attributes yet.
                        <a href="{{ route('admin.product-attributes.create') }}" wire:navigate class="text-indigo-500 hover:underline">
                            Create one
                        </a>.
                    </p>
                @else
                    <flux:field>
                        <flux:label>Attributes</flux:label>
                        <flux:checkbox.group wire:model.live="variationActiveAttributes" variant="pills">
                            @foreach ($this->productAttributes as $attribute)
                                <flux:checkbox value="{{ $attribute->name }}" label="{{ $attribute->name }}" />
                            @endforeach
                        </flux:checkbox.group>
                    </flux:field>
                @endif
            </div>

            {{-- Attribute value checkboxes — only for the attributes picked above --}}
            @if ($variationActiveAttributes !== [])
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-4 mb-4">
                    <div class="space-y-4">
                        @foreach ($this->productAttributes as $attribute)
                            @continue (! in_array($attribute->name, $variationActiveAttributes, true))
                            <flux:field>
                                <flux:label>{{ $attribute->name }}</flux:label>
                                @if (empty($attribute->values))
                                    <p class="text-xs text-zinc-400 mt-1.5">
                                        No values yet.
                                        <a href="{{ route('admin.product-attributes.edit', $attribute->id) }}" wire:navigate class="text-indigo-500 hover:underline">
                                            Add some
                                        </a>.
                                    </p>
                                @else
                                    <flux:checkbox.group wire:model.live="variationSelectedValues.{{ $attribute->name }}" variant="pills">
                                        @foreach ($attribute->values as $valueOption)
                                            <flux:checkbox value="{{ $valueOption }}" label="{{ $valueOption }}" />
                                        @endforeach
                                    </flux:checkbox.group>
                                @endif
                            </flux:field>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($variations as $i => $row)
                    <div wire:key="variation-{{ $i }}"
                        class="group/var rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden transition-shadow hover:shadow-md {{ ($row['visible'] ?? true) ? '' : 'opacity-60' }}">
                        <div class="flex items-center justify-between gap-2 px-3.5 py-2.5 border-b border-zinc-100 bg-linear-to-r from-violet-50/70 to-transparent">
                            <div class="min-w-0 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-sm">
                                @foreach ($row['attributes'] as $attributeName => $value)
                                    @if (! $loop->first)
                                        <span class="text-zinc-300">·</span>
                                    @endif
                                    <span class="font-semibold text-zinc-800 truncate">{{ $attributeName }}</span>
                                    <span class="font-medium text-violet-600 truncate">{{ $value }}</span>
                                @endforeach
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                <flux:tooltip content="Show on the storefront">
                                    <flux:switch wire:model.live="variations.{{ $i }}.visible" size="sm" />
                                </flux:tooltip>
                                <button type="button" wire:click="removeVariation({{ $i }})"
                                    class="rounded-lg p-1 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="p-3 grid grid-cols-3 gap-2">
                            <flux:field>
                                <flux:label class="text-[11px] text-zinc-500">Price</flux:label>
                                <flux:input type="number" step="0.01" min="0" size="sm"
                                    wire:model="variations.{{ $i }}.price" placeholder="Base" />
                                <flux:error name="variations.{{ $i }}.price" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="text-[11px] text-zinc-500">Discount</flux:label>
                                <flux:input type="number" step="0.01" min="0" size="sm"
                                    wire:model="variations.{{ $i }}.discount_price" placeholder="None" />
                                <flux:error name="variations.{{ $i }}.discount_price" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="text-[11px] text-zinc-500">Qty</flux:label>
                                <flux:input type="number" step="1" min="0" size="sm"
                                    wire:model="variations.{{ $i }}.quantity" placeholder="∞" />
                            </flux:field>
                            <div class="col-span-3">
                                <flux:error name="variations.{{ $i }}.quantity" />
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="sm:col-span-2 rounded-xl border-2 border-dashed border-zinc-200 py-10 px-6 text-center">
                        <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-violet-500/10 text-violet-500">
                            <flux:icon.adjustments-horizontal class="size-5" />
                        </div>
                        <p class="text-sm font-medium text-zinc-600">No variations yet</p>
                        <p class="mt-1 text-xs text-zinc-400 max-w-sm mx-auto">Pick the attributes and values that apply above if this product needs option-based pricing — cards appear automatically.</p>
                    </div>
                @endforelse
            </div>
        </x-admin-section-card>

        {{-- FAQ --}}
        <x-admin-section-card icon="question-mark-circle" title="FAQ" icon-color="bg-sky-500/10 text-sky-600"
            description="Product-specific questions and answers, shown in a FAQ section on the product page."
            collapsible :collapsed="true">

            <div class="space-y-3">
                @forelse ($faqs as $i => $row)
                    <div wire:key="faq-{{ $i }}" class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden {{ ($row['is_active'] ?? true) ? '' : 'opacity-60' }}">
                        <div class="flex items-center justify-between gap-2 px-3.5 py-2.5 border-b border-zinc-100 bg-linear-to-r from-sky-50/70 to-transparent">
                            <span class="text-xs font-semibold text-zinc-500">Question {{ $i + 1 }}</span>
                            <div class="flex items-center gap-2">
                                <flux:tooltip content="Show on the storefront">
                                    <flux:switch wire:model.live="faqs.{{ $i }}.is_active" size="sm" />
                                </flux:tooltip>
                                <button type="button" wire:click="removeFaq({{ $i }})"
                                    class="rounded-lg p-1 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="p-3.5 space-y-3">
                            <flux:field>
                                <flux:label>Question</flux:label>
                                <flux:input wire:model="faqs.{{ $i }}.question" placeholder="e.g. What is the warranty period?" />
                                <flux:error name="faqs.{{ $i }}.question" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Answer</flux:label>
                                <flux:textarea wire:model="faqs.{{ $i }}.answer" rows="3" placeholder="Answer" />
                            </flux:field>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-zinc-200 py-8 px-6 text-center">
                        <p class="text-sm font-medium text-zinc-600">No FAQs yet</p>
                        <p class="mt-1 text-xs text-zinc-400">Add a question and answer to show a FAQ section on this product's page.</p>
                    </div>
                @endforelse
            </div>

            <flux:button variant="ghost" size="sm" icon="plus" wire:click="addFaq" class="mt-3">
                Add Question
            </flux:button>
        </x-admin-section-card>

        @include('partials.admin-seo-fields')

        <div class="flex items-center gap-3 flex-wrap">
            <x-admin-save-button :label="$productId ? 'Update Product' : 'Create Product'" />
        </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-3">

            {{-- Brand --}}
            <x-admin-section-card icon="star" title="Brand" icon-color="bg-yellow-500/10 text-yellow-600"
                body-class="px-4 py-3" description="Optional — which brand this product belongs to.">
                <select wire:model="brand_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                    <option value="">No brand</option>
                    @foreach ($this->productBrands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
                @if ($this->productBrands->isEmpty())
                    <p class="text-xs text-zinc-400 mt-1.5">No brands yet.
                        <a href="{{ route('admin.product-brands.create') }}" wire:navigate class="text-indigo-500 hover:underline">
                            Create one
                        </a>.
                    </p>
                @endif
                <flux:error name="brand_id" />
            </x-admin-section-card>

            {{-- Categories --}}
            <x-admin-section-card icon="tag" title="Categories" icon-color="bg-amber-500/10 text-amber-600"
                body-class="px-4 py-3" description="A product can belong to more than one category.">
                <flux:checkbox.group wire:model="category_ids" class="flex-col items-stretch gap-0 max-h-72 overflow-y-auto border border-zinc-200 rounded-lg p-2">
                    @forelse ($this->categoryTree as $cat)
                        <div class="rounded-md py-0 hover:bg-zinc-50 transition-colors [&_ui-label]:text-xs [&_ui-label]:leading-4 [&_ui-checkbox]:size-4" style="padding-left: {{ 8 + $cat->depth * 8 }}px">
                            <flux:checkbox value="{{ $cat->id }}"
                                label="{{ $cat->getTranslation('name', \App\Support\Locale::primary(), false) }}" />
                        </div>
                    @empty
                        <p class="text-xs text-zinc-400 px-2 py-1">No categories yet — create one from Product Categories first.</p>
                    @endforelse
                </flux:checkbox.group>
                <flux:error name="category_ids" />
            </x-admin-section-card>

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
