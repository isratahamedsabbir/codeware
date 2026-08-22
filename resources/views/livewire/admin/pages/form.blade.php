<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.pages') }}" wire:navigate class="border border-zinc-800 rounded!">
            Back
        </flux:button>
    @endpush

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
                        <flux:label>Title <span class="text-red-500 ml-0.5">*</span></flux:label>
                        <flux:input wire:model.live.debounce.400ms="title_en" placeholder="Page title in English" />
                        <flux:error name="title_en" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-title" :disabled="$this->isLinked()" />
                        @if ($this->isLinked())
                            <p class="text-xs text-zinc-400 mt-1">Managed on the linked product/post/category — edit it from there</p>
                        @elseif ($slugAvailable === false)
                            <p class="text-xs text-red-500 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>This slug is already taken</p>
                        @elseif ($slugAvailable === true && $slug !== '')
                            <p class="text-xs text-green-600 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>This slug is available</p>
                        @else
                            <p class="text-xs text-zinc-400 mt-1">Auto-generated from the English title as you type — edit it if you'd like a different one</p>
                        @endif
                        <flux:error name="slug" />
                    </flux:field>
                </div>

                {{-- Bengali --}}
                <div x-show="locale==='bn'" class="space-y-4">
                    <flux:field>
                        <flux:label>শিরোনাম <span class="text-red-500 ml-0.5">*</span></flux:label>
                        <flux:input wire:model="title_bn" placeholder="বাংলায় পেজের শিরোনাম" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-title" :disabled="$this->isLinked()" />
                        @if ($this->isLinked())
                            <p class="text-xs text-zinc-400 mt-1">Managed on the linked product/post/category — edit it from there</p>
                        @elseif ($slugAvailable === false)
                            <p class="text-xs text-red-500 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>This slug is already taken</p>
                        @elseif ($slugAvailable === true && $slug !== '')
                            <p class="text-xs text-green-600 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>This slug is available</p>
                        @else
                            <p class="text-xs text-zinc-400 mt-1">Auto-generated from the English title as you type — edit it if you'd like a different one</p>
                        @endif
                        <flux:error name="slug" />
                    </flux:field>
                </div>

            </div>

            {{-- Template --}}
            <div class="mt-6 pt-5 border-t border-zinc-100">
                <flux:field>
                    <flux:label>Template</flux:label>
                    <flux:input wire:model="template" placeholder="puck" />
                </flux:field>
            </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            {{-- Page Settings --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Page Settings
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
                <div class="px-4 py-3">
                    <flux:field>
                        <flux:label>Sort Order</flux:label>
                        <flux:input type="number" wire:model="sort_order" min="0" />
                    </flux:field>
                </div>
            </div>

            {{-- Page Builder --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Page Builder
                </div>
                <div class="px-4 py-3">
                    @if ($pageId)
                        <flux:button size="xs" variant="outline" icon="arrow-top-right-on-square"
                            wire:click="openPuckEditor" class="w-full justify-center">
                            Open Page Builder
                        </flux:button>
                    @else
                        <flux:button size="xs" variant="outline" wire:click="saveAndOpenPageBuilder"
                            class="w-full justify-center">
                            Save & Open Builder
                        </flux:button>
                        <p class="text-[10px] text-zinc-400 mt-2 leading-relaxed">Save the page first to unlock the
                            visual page builder.</p>
                    @endif
                </div>
            </div>

            <livewire:admin.media-library.picker-modal />

        </div>

    </div>

    {{-- ── SEO ── --}}
    <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden mt-5">

        <div class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
            Search Engine (SEO) Settings
        </div>

        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-4">

            {{-- Left: meta fields --}}
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Meta Title</flux:label>
                    <flux:input wire:model="seo_title" placeholder="SEO-optimized title" />
                    <flux:error name="seo_title" />
                </flux:field>
                <flux:field>
                    <flux:label>Meta Description</flux:label>
                    <flux:textarea wire:model="seo_description" rows="3"
                        placeholder="Brief description for search engines…" />
                    <flux:error name="seo_description" />
                </flux:field>
                <flux:field>
                    <flux:label>OG Title</flux:label>
                    <flux:input wire:model="og_title" placeholder="Title shown when shared on social media" />
                    <flux:error name="og_title" />
                </flux:field>
                <flux:field>
                    <flux:label>OG Description</flux:label>
                    <flux:textarea wire:model="og_description" rows="3"
                        placeholder="Description shown when shared on social media" />
                    <flux:error name="og_description" />
                </flux:field>
            </div>

            {{-- Right: OG image + robots toggles --}}
            <div class="space-y-4">
                <flux:field>
                    <flux:label>OG Image (1200×630)</flux:label>
                    <x-media-picker model="og_image" label="" placeholder="Select OG image from library"
                        :picker-id="$ogImagePickerId" />
                </flux:field>

                <div class="border-t border-zinc-100 pt-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-zinc-700">No-Index</p>
                            <p class="text-xs text-zinc-400">Prevent search engines from indexing this page</p>
                        </div>
                        <flux:switch wire:model="no_index" />
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-zinc-700">No-Follow</p>
                            <p class="text-xs text-zinc-400">Prevent search engines from following links on this page</p>
                        </div>
                        <flux:switch wire:model="no_follow" />
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Footer --}}
    <div class="flex justify-end items-center gap-3 border-t border-zinc-100 flex-wrap pt-4 mt-5">
        <a href="{{ route('admin.pages') }}" wire:navigate
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
            <span wire:loading.remove wire:target="save">{{ $pageId ? 'Update Page' : 'Create Page' }}</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>
</div>
