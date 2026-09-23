<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left"
            href="{{ route('admin.categories', ['type' => $type]) }}" wire:navigate>
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
                                Name
                                @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                            </flux:label>
                            <flux:input wire:model.live.debounce.400ms="name.{{ $language->code }}"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Category name' : 'Category name ('.($language->native_name ?: $language->name).')' }}" />
                            @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                        </flux:field>

                        @if ($type === \App\Models\Category::TYPE_POST)
                            <flux:field class="mt-4">
                                <flux:label>Description</flux:label>
                                <flux:textarea wire:model="description.{{ $language->code }}" rows="3"
                                    placeholder="{{ $language->code === $this->primaryLocale ? 'Short description' : 'Short description ('.($language->native_name ?: $language->name).')' }}" />
                            </flux:field>
                        @endif
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
                </x-admin-locale-tabs>

            {{-- Type — locked to whichever pool this category opened from; only
                 meaningful to change while creating (see Form::updatedType()). --}}
            <div class="mt-4" wire:key="category-type-panel">
                <flux:field>
                    <flux:label>Type<x-field-hint text="Product categories show on the Product form, post categories on the Post form" /></flux:label>
                    <flux:select wire:model="type">
                        <flux:select.option value="{{ \App\Models\Category::TYPE_PRODUCT }}">Product</flux:select.option>
                        <flux:select.option value="{{ \App\Models\Category::TYPE_POST }}">Post</flux:select.option>
                    </flux:select>
                    <flux:error name="type" />
                </flux:field>
            </div>
        </div>

        @include('partials.admin-seo-fields')

        <div class="flex items-center gap-3 flex-wrap">
            <x-admin-save-button :label="$categoryId ? 'Update Category' : 'Create Category'" />
        </div>
        </div>

        {{-- ── SIDEBAR — product categories only ── --}}
        @if ($type === \App\Models\Category::TYPE_PRODUCT)
            <div class="w-[320px] shrink-0 space-y-4">
                <x-admin-section-card icon="swatch" title="Category Settings" body-class="px-4 py-4 space-y-4"
                    description="Icon and parent category.">
                    <flux:field>
                        <flux:label>Parent category<x-field-hint text="Leave as top-level, or nest this under an existing category to make it a subcategory." /></flux:label>
                        <flux:select wire:model="parentId">
                            <flux:select.option value="">— None (top-level) —</flux:select.option>
                            @foreach ($this->parentOptions as $option)
                                <flux:select.option value="{{ $option['id'] }}">{{ $option['label'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="parentId" />
                    </flux:field>

                    <x-media-picker model="icon" label="Icon" size-hint="64 × 64, transparent" placeholder="Select icon image from library"
                        :picker-id="$iconPickerId" mimes="png,webp" :max-size-mb="1" only-images dropzone />
                </x-admin-section-card>
            </div>
        @endif
    </div>

    <livewire:admin.media-library.picker-modal key="categories-form-picker-modal" />
</div>
