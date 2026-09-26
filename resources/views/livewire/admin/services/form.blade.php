<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.services') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full max-w-2xl bg-white rounded-[5px] shadow-sm p-6 space-y-3">

        <x-admin-locale-tabs>
            @foreach (\App\Support\Locale::translatable() as $language)
                <x-admin-locale-panel :code="$language->code">
                    <flux:field>
                        <flux:label :badge="$language->code">
                            Name
                            @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                        </flux:label>
                        <flux:input wire:model="name.{{ $language->code }}"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'Service name' : 'Service name ('.($language->native_name ?: $language->name).')' }}" />
                        @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label :badge="$language->code">Description</flux:label>
                        <flux:textarea wire:model="description.{{ $language->code }}" rows="4"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'What this service includes' : 'Description ('.($language->native_name ?: $language->name).')' }}" />
                        <flux:error name="description.{{ $language->code }}" />
                    </flux:field>
                </x-admin-locale-panel>
            @endforeach

            <flux:field>
                <flux:label>Slug<x-field-hint text="Leave blank to auto-generate from the primary language's name" /></flux:label>
                <flux:input wire:model="slug" placeholder="auto-generated-from-name" />
                <flux:error name="slug" />
            </flux:field>
        </x-admin-locale-tabs>

        <flux:field>
            <flux:label>Price <span class="text-red-500 ml-0.5">*</span></flux:label>
            <flux:input type="number" step="0.01" min="0" wire:model="price" placeholder="0.00" />
            <flux:error name="price" />
        </flux:field>

        <x-media-picker model="featuredImage" label="Service Image" size-hint="1200 × 675" placeholder="Select an image from library" mimes="jpg,jpeg,png,webp,avif,svg" only-images dropzone />

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$serviceId ? 'Update Service' : 'Create Service'" />
        </div>

    </div>
</div>
