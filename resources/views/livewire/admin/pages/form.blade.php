<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.pages') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 space-y-4">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div x-data="{ locale: 'en' }">
                <div class="flex gap-2 -mx-6 px-6 pb-4 mb-4 border-b border-zinc-200 dark:border-zinc-700">
                    <button type="button"
                        :class="locale === 'en' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                        @click="locale='en'">EN</button>
                    <button type="button"
                        :class="locale === 'bn' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                        @click="locale='bn'">বাং</button>
                </div>
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
        </div>

        <x-admin-section-card icon="magnifying-glass" title="Search Engine (SEO) Settings"
            icon-color="bg-sky-500/10 text-sky-600" body-class="grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-4"
            description="Meta tags and indexing controls for this page.">
                <div class="lg:col-span-2 min-w-0">
                    <flux:field>
                        <flux:label>Canonical URL</flux:label>
                        <div class="flex items-center gap-2">
                            <select wire:model="canonical_base"
                                class="rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700 shrink-0">
                                <option value="">Select base…</option>
                                @foreach ($this->canonicalBaseOptions() as $base)
                                    <option value="{{ $base }}">{{ $base }}</option>
                                @endforeach
                            </select>
                            <flux:input wire:model.live.debounce.400ms="canonical_slug" placeholder="page-slug" class="flex-1" />
                        </div>
                        <p class="text-xs text-zinc-400 mt-1">
                            The base is managed in Settings → SEO → Canonical Base Links. The path defaults to this page's slug but can be edited independently.
                        </p>
                        <flux:error name="canonical_base" />
                        <flux:error name="canonical_slug" />
                    </flux:field>
                </div>
                <div class="space-y-4 min-w-0">
                    <flux:field>
                        <flux:label>Meta Title</flux:label>
                        <flux:input wire:model="seo_title" placeholder="SEO-optimized title" />
                        <flux:error name="seo_title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Meta Description</flux:label>
                        <flux:textarea wire:model="seo_description" class="h-24" placeholder="Brief description for search engines…" />
                        <flux:error name="seo_description" />
                    </flux:field>
                    <flux:field>
                        <flux:label>OG Title</flux:label>
                        <flux:input wire:model="og_title" placeholder="Title shown when shared on social media" />
                        <flux:error name="og_title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>OG Description</flux:label>
                        <flux:textarea wire:model="og_description" class="h-24" placeholder="Description shown when shared on social media" />
                        <flux:error name="og_description" />
                    </flux:field>
                </div>
                <div class="space-y-4 min-w-0">
                    <flux:field>
                        <flux:label>OG Image (1200×630)</flux:label>
                        <x-media-picker model="og_image" label="" placeholder="Select OG image from library"
                            :picker-id="$ogImagePickerId" dropzone />
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
        </x-admin-section-card>

        {{-- ── Constant ── --}}
        <x-admin-section-card icon="variable" title="Constant" icon-color="bg-indigo-500/10 text-indigo-600"
            description="Freeform key/value pairs — custom flags or extra content.">
            <x-slot:actions>
                <flux:button size="xs" variant="outline" icon="plus" wire:click="addConstant">Add field</flux:button>
            </x-slot:actions>

                <flux:error name="constant" />

                @forelse ($constant as $i => $pair)
                    <div wire:key="constant-row-{{ $i }}" class="group relative rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 transition-colors hover:border-zinc-300">
                        <div class="flex items-start gap-3">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-[11px] font-semibold text-white">
                                {{ $i + 1 }}
                            </div>

                            <div class="flex-1 min-w-0 space-y-3">
                                <flux:field>
                                    <flux:label>Value type</flux:label>
                                    <div class="grid grid-cols-2 gap-1.5 rounded-lg bg-zinc-100 p-1">
                                        <button type="button" wire:click="setConstantType({{ $i }}, 'textarea')"
                                            class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ ($pair['type'] ?? 'textarea') === 'textarea' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10" />
                                            </svg>
                                            Textarea
                                        </button>
                                        <button type="button" wire:click="setConstantType({{ $i }}, 'file')"
                                            class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ ($pair['type'] ?? 'textarea') === 'file' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                <polyline points="14 2 14 8 20 8" />
                                            </svg>
                                            File
                                        </button>
                                    </div>
                                </flux:field>

                                <flux:field>
                                    <flux:label>Key</flux:label>
                                    <flux:input wire:model.live="constant.{{ $i }}.key" placeholder="e.g. og_type" class="font-mono" />
                                    <flux:error name="constant.{{ $i }}.key" />
                                </flux:field>

                                @if (($pair['type'] ?? 'textarea') === 'file')
                                    <x-media-picker model="constant.{{ $i }}.value" label="Value" dropzone />
                                    <flux:error name="constant.{{ $i }}.value" />
                                @else
                                    <flux:field>
                                        <flux:label>Value</flux:label>
                                        <flux:textarea wire:model="constant.{{ $i }}.value" class="h-24" placeholder="e.g. website" />
                                        <flux:error name="constant.{{ $i }}.value" />
                                    </flux:field>
                                @endif
                            </div>

                            <button type="button" wire:click="removeConstant({{ $i }})"
                                class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove field">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-200 py-10 text-center">
                        <svg class="mx-auto mb-2 h-8 w-8 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <p class="text-sm text-zinc-400">No content yet.</p>
                    </div>
                @endforelse
        </x-admin-section-card>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">
            <x-admin-section-card icon="cog-6-tooth" title="Page Settings" body-class="px-4 py-3"
                description="Template used to render this page.">
                <flux:field>
                    <flux:label>Template</flux:label>
                    <flux:input wire:model="template" placeholder="puck" />
                </flux:field>
            </x-admin-section-card>

            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm p-4 flex items-center gap-3 flex-wrap">
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

            <livewire:admin.media-library.picker-modal key="pages-form-picker-modal" />
        </div>
    </div>

</div>
