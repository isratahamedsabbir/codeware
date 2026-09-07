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
                    <x-admin-locale-panel :code="$language->code">
                        <flux:field>
                            <flux:label>
                                Title
                                @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                            </flux:label>
                            <flux:input wire:model.live.debounce.400ms="title.{{ $language->code }}"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Post title' : 'Post title ('.($language->native_name ?: $language->name).')' }}" />
                            @if ($language->code === $this->primaryLocale)<flux:error name="title.{{ $language->code }}" />@endif
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

            {{-- Tags --}}
            <x-admin-section-card icon="tag" title="Tags" body-class="px-4 py-3"
                description="Label this post for filtering and search.">
                @forelse ($this->tags as $tag)
                    <label class="flex items-center gap-2.5 py-1.5 cursor-pointer group">
                        <input type="checkbox" wire:model="tag_ids" value="{{ $tag->id }}"
                            class="w-4 h-4 rounded border-zinc-300 text-indigo-500 focus:ring-indigo-400 cursor-pointer" />
                        <span class="text-sm text-zinc-700 group-hover:text-zinc-900 transition-colors">
                            {{ $tag->getTranslation('name', \App\Support\Locale::primary(), false) }}
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
            </x-admin-section-card>

            {{-- Featured Image --}}
            <x-admin-section-card icon="photo" title="Featured Image" icon-color="bg-blue-500/10 text-blue-600"
                body-class="px-4 py-4" description="Shown in post listings and social shares. Recommended 1200×675px.">
                <x-media-picker model="featured_image" label="" placeholder="Select image"
                    :picker-id="$featuredImagePickerId" mimes="jpg,jpeg,png,webp" only-images dropzone />
            </x-admin-section-card>

            <livewire:admin.media-library.picker-modal key="posts-form-picker-modal" />

        </div>

    </div>
</div>
