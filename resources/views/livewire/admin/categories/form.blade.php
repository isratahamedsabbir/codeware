<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left"
            href="{{ route('admin.categories', ['type' => $typeId]) }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="flex gap-5 items-start">
        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 space-y-5">
        <x-admin-section-card variant="postbox" persist-key="category-details" title="Category Details" :collapsed="false" body-class="p-3">

            <x-admin-locale-tabs>
                @foreach (\App\Support\Locale::translatable() as $language)
                    <x-admin-locale-panel :code="$language->code">
                        <flux:field>
                            <flux:label :badge="$language->code">
                                Name
                                @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                            </flux:label>
                            <flux:input wire:model.live.debounce.400ms="name.{{ $language->code }}"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Category name' : 'Category name ('.($language->native_name ?: $language->name).')' }}" />
                            @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                        </flux:field>

                        @if (! $this->isProductPool)
                            <flux:field class="mt-4">
                                <flux:label :badge="$language->code">Description</flux:label>
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

            {{-- Type — preselected to whichever pool this category opened from;
                 only meaningful to change while creating (see Form::updatedTypeId()). --}}
            <div class="mt-4" wire:key="category-type-panel">
                <flux:field>
                    <flux:label>Type<span class="text-red-500 ml-0.5">*</span><x-field-hint text="Product categories show on the Product form, post categories on the Post form. Manage the list of types under Accessories → Types" /></flux:label>
                    <flux:select wire:model="typeId" placeholder="Select a type">
                        @foreach ($this->typeOptions as $typeOption)
                            <flux:select.option value="{{ $typeOption->id }}">{{ $typeOption->getTranslation('name', $this->primaryLocale, false) ?: $typeOption->getTranslation('name', 'en', false) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="typeId" />
                </flux:field>
            </div>
        </x-admin-section-card>

        @include('partials.admin-seo-fields', ['seoCardVariant' => 'postbox', 'seoPersistKey' => 'category-seo'])

        <div class="flex items-center gap-3 flex-wrap">
            <x-admin-save-button :label="$categoryId ? 'Update Category' : 'Create Category'" />
        </div>
        </div>

        {{-- ── SIDEBAR — product categories only ── --}}
        @if ($this->isProductPool)
            <div class="w-[320px] shrink-0 space-y-5">
                <x-admin-section-card variant="postbox" persist-key="category-settings" title="Category Settings" :collapsed="false"
                    body-class="p-3 space-y-4" description="Icon and parent category.">
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
                        :picker-id="$iconPickerId" mimes="png,webp,avif" :max-size-mb="1" only-images dropzone />
                </x-admin-section-card>
            </div>
        @endif
    </div>

    <livewire:admin.media-library.picker-modal key="categories-form-picker-modal" />
</div>
