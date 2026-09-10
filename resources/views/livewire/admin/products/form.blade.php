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

                {{-- Price (not translatable — shown regardless of locale tab) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
                    <flux:field>
                        <flux:label>Price</flux:label>
                        <flux:input type="number" wire:model="price" min="0" step="0.01" />
                        <flux:error name="price" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Discount Price</flux:label>
                        <flux:input type="number" wire:model="discount_price" min="0" step="0.01" placeholder="No discount" />
                        <p class="text-xs text-zinc-400 mt-1">Shown as a strikethrough sale price. Leave blank for no discount.</p>
                        <flux:error name="discount_price" />
                    </flux:field>
                </div>

            </x-admin-locale-tabs>
        </div>

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
