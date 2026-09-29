<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.product-brands') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <x-admin-section-card variant="postbox" title="Brand Details" :collapsible="false" class="w-full max-w-2xl">

        <x-admin-locale-tabs>
            @foreach (\App\Support\Locale::translatable() as $language)
                <x-admin-locale-panel :code="$language->code">
                    <flux:field>
                        <flux:label :badge="$language->code">
                            Name
                            @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                        </flux:label>
                        <flux:input wire:model.live.debounce.400ms="name.{{ $language->code }}"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'e.g. Nestle, Unilever, Samsung' : 'e.g. Nestle, Unilever, Samsung ('.($language->native_name ?: $language->name).')' }}" />
                        @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                    </flux:field>
                </x-admin-locale-panel>
            @endforeach
        </x-admin-locale-tabs>

        <flux:field>
            <flux:label>Type<span class="text-red-500 ml-0.5">*</span><x-field-hint text="Product brands appear in the Product form's brand dropdown, post brands on the Post form. Manage the list of types under Accessories → Types" /></flux:label>
            <flux:select wire:model="typeId" placeholder="Select a type">
                @foreach ($this->typeOptions as $type)
                    <flux:select.option value="{{ $type->id }}">{{ $type->getTranslation('name', $this->primaryLocale, false) ?: $type->getTranslation('name', 'en', false) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="typeId" />
        </flux:field>

        <x-media-picker model="logo" label="Brand Logo" size-hint="Square, 512 × 512" placeholder="Select brand logo from library" mimes="jpg,jpeg,png,webp,avif,svg" only-images dropzone />

        {{-- Footer --}}
        <div class="-mx-3 -mb-3 mt-4 flex items-center gap-3 flex-wrap rounded-b-[3px] border-t border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$brandId ? 'Update Brand' : 'Create Brand'" />
        </div>

    </x-admin-section-card>

    <livewire:admin.media-library.picker-modal key="product-brands-form-picker-modal" />
</div>