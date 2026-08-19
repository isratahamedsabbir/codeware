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

    <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.products') }}" wire:navigate class="mb-4 border-2">
        Back
    </flux:button>

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 bg-white rounded-lg shadow-sm p-6">

            <div x-data="{ locale: 'en' }">

                {{-- Locale Tabs --}}
                <div class="flex gap-2 mb-4">
                    <button type="button"
                        :class="locale === 'en' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                        @click="locale='en'">EN</button>
                    <button type="button"
                        :class="locale === 'bn' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                        @click="locale='bn'">বাং</button>
                </div>

                {{-- English --}}
                <div x-show="locale==='en'" class="space-y-4">
                    <flux:field>
                        <flux:label>Name <span class="text-red-500 ml-0.5">*</span></flux:label>
                        <flux:input wire:model.live.debounce.400ms="name_en" placeholder="Product name in English" />
                        <flux:error name="name_en" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-name" />
                        @if ($slugAvailable === false)
                            <p class="text-xs text-red-500 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>This slug is already taken</p>
                        @elseif ($slugAvailable === true && $slug !== '')
                            <p class="text-xs text-green-600 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>This slug is available</p>
                        @else
                            <p class="text-xs text-zinc-400 mt-1">Auto-generated from the English name as you type — edit it if you'd like a different one</p>
                        @endif
                        <flux:error name="slug" />
                    </flux:field>
                </div>

                {{-- Bengali --}}
                <div x-show="locale==='bn'" class="space-y-4">
                    <flux:field>
                        <flux:label>নাম</flux:label>
                        <flux:input wire:model="name_bn" placeholder="বাংলায় পণ্যের নাম" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-name" />
                        @if ($slugAvailable === false)
                            <p class="text-xs text-red-500 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>This slug is already taken</p>
                        @elseif ($slugAvailable === true && $slug !== '')
                            <p class="text-xs text-green-600 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>This slug is available</p>
                        @else
                            <p class="text-xs text-zinc-400 mt-1">Auto-generated from the English name as you type — edit it if you'd like a different one</p>
                        @endif
                        <flux:error name="slug" />
                    </flux:field>
                </div>

            </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            {{-- Featured Image --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Thumbnail Image
                </div>
                <div class="px-4 py-4">
                    <x-media-picker model="featured_image" label="" placeholder="Select featured image"
                        :picker-id="$featuredImagePickerId" />
                </div>
            </div>

            {{-- Status & Order --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Settings
                </div>
                <div class="px-4 py-3 border-b border-zinc-50">
                    <flux:field>
                        <flux:label>Category</flux:label>
                        <flux:select wire:model="product_category_id">
                            <flux:select.option value="">— None —</flux:select.option>
                            @foreach ($this->productCategories as $cat)
                                <flux:select.option value="{{ $cat->id }}">
                                    {{ $cat->getTranslation('name', 'en', false) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="product_category_id" />
                    </flux:field>
                </div>
                <div class="px-4 py-3 border-b border-zinc-50">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="active">Active</flux:select.option>
                            <flux:select.option value="inactive">Inactive</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>
                <div class="px-4 py-3 border-b border-zinc-50">
                    <flux:field>
                        <flux:label>Price</flux:label>
                        <flux:input type="number" wire:model="price" min="0" step="0.01" />
                        <flux:error name="price" />
                    </flux:field>
                </div>
                <div class="px-4 py-3 border-b border-zinc-50">
                    <flux:field>
                        <flux:label>Sort Order</flux:label>
                        <flux:input type="number" wire:model="sort_order" min="0" />
                    </flux:field>
                </div>
                <div class="px-4 py-3">
                    <flux:checkbox wire:model="is_featured" label="Featured" />
                </div>
            </div>

            {{-- Page --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Page
                </div>
                <div class="px-4 py-3">
                    @if ($pageId)
                        <flux:button size="xs" variant="outline" icon="arrow-top-right-on-square"
                            href="{{ route('admin.pages.edit', $pageId) }}" wire:navigate class="w-full justify-center">
                            Edit Page
                        </flux:button>
                    @else
                        <p class="text-[10px] text-zinc-400 leading-relaxed">A page will be created automatically when
                            you save.</p>
                    @endif
                </div>
            </div>

            {{-- Page Builder --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Page Builder
                </div>
                <div class="px-4 py-3">
                    @if ($productId)
                        <flux:button size="xs" variant="outline" icon="arrow-top-right-on-square"
                            wire:click="openPuckEditor" class="w-full justify-center">
                            Open Page Builder
                        </flux:button>
                    @else
                        <flux:button size="xs" variant="outline" wire:click="saveAndOpenPageBuilder"
                            class="w-full justify-center">
                            Save & Open Builder
                        </flux:button>
                        <p class="text-[10px] text-zinc-400 mt-2 leading-relaxed">Save the product first to unlock the
                            visual page builder.</p>
                    @endif
                </div>
            </div>

            <livewire:admin.media-library.picker-modal />

        </div>

    </div>

    {{-- Footer --}}
    <div class="flex justify-end items-center gap-3 border-t border-zinc-100 flex-wrap pt-4 mt-5">
        <a href="{{ route('admin.products') }}" wire:navigate
            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-[5px] border text-red-600 border-red-200 bg-white hover:bg-red-50 hover:border-red-400 transition-colors h-10">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
            Cancel
        </a>
        <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
            class="admin-btn-save inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
            <svg wire:loading.remove wire:target="save" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                <polyline points="17 21 17 13 7 13 7 21" />
                <polyline points="7 3 7 8 15 8" />
            </svg>
            <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9" stroke-opacity="0.25" />
                <path d="M21 12a9 9 0 0 0-9-9" stroke-opacity="1" />
            </svg>
            <span wire:loading.remove wire:target="save">{{ $productId ? 'Update Product' : 'Create Product' }}</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>
</div>
