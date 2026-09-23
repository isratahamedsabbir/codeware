<div class="max-w-[1600px] w-full mx-auto flex-1">

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

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.posts') }}" wire:navigate>
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
                                Title
                                @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                            </flux:label>
                            <flux:input wire:model.live.debounce.400ms="title.{{ $language->code }}"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Post title' : 'Post title ('.($language->native_name ?: $language->name).')' }}" />
                            @if ($language->code === $this->primaryLocale)<flux:error name="title.{{ $language->code }}" />@endif
                        </flux:field>

                        <flux:field>
                            <flux:label>Description<x-field-hint text="Shown in blog listings and as a fallback description — the full post body is built separately in the page builder." /></flux:label>
                            <flux:textarea wire:model.live.debounce.400ms="description.{{ $language->code }}" rows="4"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Short post summary' : 'Short post summary ('.($language->native_name ?: $language->name).')' }}" />
                            @if ($language->code === $this->primaryLocale)<flux:error name="description.{{ $language->code }}" />@endif
                        </flux:field>
                    </x-admin-locale-panel>
                @endforeach

                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-title" />
                        @if ($slugAvailable === false)
                            <p class="text-xs text-red-500 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>This slug is already taken</p>
                        @elseif ($slugAvailable === true && $slug !== '')
                            <p class="text-xs text-green-600 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>This slug is available</p>
                        @else
                            <p class="text-xs text-zinc-400 mt-1">Auto-generated from the primary language's title as you type — edit it if you'd like a different one</p>
                        @endif
                        <flux:error name="slug" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Category</flux:label>
                        <flux:select wire:model="category_id">
                            <flux:select.option value="">No category</flux:select.option>
                            @foreach ($this->categories as $cat)
                                <flux:select.option :value="$cat->id">
                                    {{ $cat->getTranslation('name', \App\Support\Locale::primary(), false) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
            </x-admin-locale-tabs>
        </div>

        @include('partials.admin-seo-fields')

        <div class="flex items-center gap-3 flex-wrap">
            <x-admin-save-button :label="$postId ? 'Update Post' : 'Create Post'" />
        </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            {{-- Tags — product-typed and post-typed pools, no legacy (App\Models\Tag) --}}
            <x-admin-section-card icon="tag" title="Tags" body-class="px-4 py-3"
                description="Label this post for filtering and search.">
                <div
                    x-data="{
                        tagIds: @entangle('tag_ids'),
                        allTags: @js($this->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->getTranslation('name', \App\Support\Locale::primary(), false)])),
                        query: '',
                        open: false,
                        panelStyle: '',
                        get filtered() {
                            const q = this.query.trim().toLowerCase();
                            return this.allTags.filter(t => !this.tagIds.includes(t.id) && (!q || t.name.toLowerCase().includes(q)));
                        },
                        get selected() {
                            return this.tagIds.map(id => this.allTags.find(t => t.id === id)).filter(Boolean);
                        },
                        updatePosition() {
                            this.$nextTick(() => {
                                const trigger = this.$refs.tagBox;
                                if (!trigger) return;
                                const rect = trigger.getBoundingClientRect();
                                this.panelStyle = `top:${rect.bottom + 4}px; left:${rect.left}px; width:${rect.width}px;`;
                            });
                        },
                        openDropdown() {
                            this.open = true;
                            this.updatePosition();
                        },
                        addTag(id) {
                            if (!this.tagIds.includes(id)) this.tagIds.push(id);
                            this.query = '';
                            this.$refs.tagSearch.focus();
                        },
                        removeTag(id) {
                            this.tagIds = this.tagIds.filter(existing => existing !== id);
                        },
                        init() {
                            const handler = (e) => {
                                if (!this.$refs.tagSearch || !document.body.contains(this.$refs.tagSearch)) {
                                    document.removeEventListener('click', handler);
                                    return;
                                }
                                if (!this.open) return;
                                if (this.$refs.tagBox && this.$refs.tagBox.contains(e.target)) return;
                                if (e.target.closest('[data-tag-panel]')) return;
                                this.open = false;
                            };
                            document.addEventListener('click', handler);
                            window.addEventListener('resize', () => this.open && this.updatePosition());
                            window.addEventListener('scroll', () => this.open && this.updatePosition(), true);
                        },
                    }"
                    class="relative"
                >
                    <div x-ref="tagBox" @click="$refs.tagSearch.focus()"
                        class="flex flex-wrap items-center gap-1.5 min-h-9 w-full rounded-lg border border-zinc-200 px-2 py-1.5 cursor-text focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100 transition-all">
                        <template x-for="tag in selected" :key="tag.id">
                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 pl-2.5 pr-1.5 py-1 text-xs font-medium text-indigo-700">
                                <span x-text="tag.name"></span>
                                <button type="button" @click.stop="removeTag(tag.id)"
                                    class="rounded-full p-0.5 hover:bg-indigo-100 transition-colors">
                                    <flux:icon name="x-mark" variant="micro" class="size-3" />
                                </button>
                            </span>
                        </template>

                        <input type="text" x-ref="tagSearch" x-model="query" @focus="openDropdown()" @input="open = true; updatePosition()"
                            placeholder="Search tags…"
                            class="flex-1 min-w-25 border-0 p-0.5 text-sm outline-none focus:ring-0" />
                    </div>

                    <template x-teleport="body">
                        <div data-tag-panel x-show="open && filtered.length" x-cloak x-transition :style="panelStyle"
                            class="fixed z-50 max-h-56 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg py-1">
                            <template x-for="tag in filtered" :key="tag.id">
                                <button type="button" @click="addTag(tag.id)"
                                    class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50 transition-colors"
                                    x-text="tag.name"></button>
                            </template>
                        </div>
                    </template>

                    <p x-show="open && query && !filtered.length" x-cloak class="mt-1 text-xs text-zinc-400">
                        No matching tags.
                    </p>

                    @if ($this->tags->isEmpty())
                        <p class="mt-1 text-xs text-zinc-400">No tags yet — create one from Tags first.</p>
                    @endif
                </div>
                <flux:error name="tag_ids" />
            </x-admin-section-card>

            {{-- Featured Image --}}
            <x-admin-section-card icon="photo" title="Featured Image" icon-color="bg-blue-500/10 text-blue-600"
                body-class="px-4 py-4" description="Shown in post listings and social shares. Recommended 1200×675px.">
                <x-media-picker model="featured_image" label="" size-hint="1200 × 675" placeholder="Select image"
                    :picker-id="$featuredImagePickerId" mimes="jpg,jpeg,png,webp" only-images dropzone />
            </x-admin-section-card>

            <livewire:admin.media-library.picker-modal key="posts-form-picker-modal" />

        </div>

    </div>
</div>
