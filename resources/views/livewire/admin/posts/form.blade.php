<div class="max-w-[1600px] w-full mx-auto flex-1">

    <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.posts') }}" wire:navigate class="mb-4 border-2">
        Back
    </flux:button>

    <style>
        /* Jodit editor fixed height — no resize on click */
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

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 bg-white rounded-lg border border-zinc-100 shadow-sm p-6">

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
                        <flux:input wire:model="title_en" placeholder="Post title in English" />
                        <flux:error name="title_en" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="slug" placeholder="auto-generated-from-title" />
                        <p class="text-xs text-zinc-400 mt-1">Leave blank to auto-generate from the English title</p>
                        <flux:error name="slug" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Description</flux:label>
                        {{-- ✅ jodit-fixed-wrap class দিয়ে height lock --}}
                        <div class="jodit-fixed-wrap">
                            <livewire:jodit-text-editor wire:model="description_en" :height="180" />
                        </div>
                    </flux:field>
                </div>

                {{-- Bengali --}}
                <div x-show="locale==='bn'" class="space-y-4">
                    <flux:field>
                        <flux:label>শিরোনাম</flux:label>
                        <flux:input wire:model="title_bn" placeholder="বাংলায় পোস্টের শিরোনাম" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="slug" placeholder="auto-generated-from-title" />
                        <p class="text-xs text-zinc-400 mt-1">Leave blank to auto-generate from the English title</p>
                        <flux:error name="slug" />
                    </flux:field>
                    <flux:field>
                        <flux:label>বিবরণ</flux:label>
                        {{-- ✅ jodit-fixed-wrap class দিয়ে height lock --}}
                        <div class="jodit-fixed-wrap">
                            <livewire:jodit-text-editor wire:model="description_bn" :height="180" />
                        </div>
                    </flux:field>
                </div>

            </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            {{-- Category & Status --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Post Settings
                </div>
                <div class="px-4 py-3 border-b border-zinc-50">
                    <flux:field>
                        <flux:label>Category</flux:label>
                        <flux:select wire:model="category_id">
                            <flux:select.option value="">No category</flux:select.option>
                            @foreach ($this->categories as $cat)
                                <flux:select.option :value="$cat->id">
                                    {{ $cat->getTranslation('name', 'en', false) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
                <div class="px-4 py-3">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="inactive">Inactive</flux:select.option>
                            <flux:select.option value="active">Active</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>

            {{-- Tags --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Tags
                </div>
                <div class="px-4 py-3">
                    @forelse ($this->tags as $tag)
                        <label class="flex items-center gap-2.5 py-1.5 cursor-pointer group">
                            <input type="checkbox" wire:model="tag_ids" value="{{ $tag->id }}"
                                class="w-4 h-4 rounded border-zinc-300 text-indigo-500 focus:ring-indigo-400 cursor-pointer" />
                            <span class="text-sm text-zinc-700 group-hover:text-zinc-900 transition-colors">
                                {{ $tag->getTranslation('name', 'en', false) }}
                            </span>
                        </label>
                    @empty
                        <p class="text-xs text-zinc-400">No tags yet.
                            <a href="{{ route('admin.tags.create') }}" wire:navigate class="text-indigo-500 hover:underline">
                                Create one
                            </a>.
                        </p>
                    @endforelse
                    <flux:error name="tag_ids" />
                </div>
            </div>

            {{-- Featured Image --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Featured Image
                </div>
                <div class="px-4 py-4">
                    <x-media-picker model="featured_image" label="" placeholder="Select image"
                        :picker-id="$featuredImagePickerId" />
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
                    @if ($postId)
                        <flux:button size="xs" variant="outline" icon="arrow-top-right-on-square"
                            wire:click="openPuckEditor" class="w-full justify-center">
                            Open Page Builder
                        </flux:button>
                    @else
                        <flux:button size="xs" variant="outline" wire:click="saveAndOpenPageBuilder"
                            wire:loading.attr="disabled" class="w-full justify-center">
                            Save & Open Builder
                        </flux:button>
                        <p class="text-[10px] text-zinc-400 mt-2 leading-relaxed">Save the post first to unlock the
                            visual page builder.</p>
                    @endif
                </div>
            </div>

            <livewire:admin.media-library.picker-modal />

        </div>

    </div>

    {{-- ── SEO ── --}}
    <div class="bg-white rounded-lg border border-zinc-100 shadow-sm p-6 mt-5">

        <div class="mb-4">
            <h2 class="text-sm font-semibold text-zinc-800">Search Engine (SEO) Settings</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-4">

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
        <a href="{{ route('admin.posts') }}" wire:navigate
            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg border text-red-600 border-red-200 bg-white hover:bg-red-50 hover:border-red-400 transition-colors">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
            Cancel
        </a>
        <button wire:click="save" wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors"
            style="background:#28A745" onmouseover="this.style.background='#218838'"
            onmouseout="this.style.background='#28A745'">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                <polyline points="17 21 17 13 7 13 7 21" />
                <polyline points="7 3 7 8 15 8" />
            </svg>
            {{ $postId ? 'Update Post' : 'Create Post' }}
        </button>
    </div>
</div>

