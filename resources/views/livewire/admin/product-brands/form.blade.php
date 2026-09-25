<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.product-brands') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full max-w-2xl bg-white rounded-[5px] shadow-sm p-6 space-y-3">

        <x-admin-locale-tabs>
            @foreach (\App\Support\Locale::active() as $language)
                <x-admin-locale-panel :code="$language->code">
                    <flux:field>
                        <flux:label>
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
            <flux:label>Type<x-field-hint text="Product brands appear in the Product form's brand dropdown, post brands on the Post form, Shared shows on both" /></flux:label>
            <flux:select wire:model="type">
                <flux:select.option value="{{ \App\Models\ProductBrand::TYPE_PRODUCT }}">Product</flux:select.option>
                <flux:select.option value="{{ \App\Models\ProductBrand::TYPE_POST }}">Post</flux:select.option>
                <flux:select.option value="">Shared (both)</flux:select.option>
            </flux:select>
            <flux:error name="type" />
        </flux:field>

        <x-media-picker model="logo" label="Brand Logo" size-hint="Square, 512 × 512" placeholder="Select brand logo from library" mimes="jpg,jpeg,png,webp,avif,svg" only-images dropzone />

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$brandId ? 'Update Brand' : 'Create Brand'" />
        </div>

    </div>
</div>