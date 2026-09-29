<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.product-vendors') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <x-admin-section-card variant="postbox" title="Vendor Details" :collapsible="false" class="w-full max-w-2xl">

        <flux:field>
            <flux:label>Name <span class="text-red-500 ml-0.5">*</span></flux:label>
            <flux:input wire:model="name" placeholder="e.g. Global Supplies Ltd." />
            <flux:error name="name" />
        </flux:field>

        <x-media-picker model="logo" label="Vendor Logo" size-hint="Square, 512 × 512" placeholder="Select vendor logo from library" mimes="jpg,jpeg,png,webp,avif,svg" only-images dropzone />

        <flux:field>
            <flux:label>Mobile</flux:label>
            <flux:input wire:model="mobile" placeholder="e.g. +880 1234-567890" />
            <flux:error name="mobile" />
        </flux:field>

        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input type="email" wire:model="email" placeholder="vendor@example.com" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>Address</flux:label>
            <flux:textarea wire:model="address" rows="3" placeholder="Vendor's business address" />
            <flux:error name="address" />
        </flux:field>

        {{-- Footer --}}
        <div class="-mx-3 -mb-3 mt-4 flex items-center gap-3 flex-wrap rounded-b-[3px] border-t border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$vendorId ? 'Update Vendor' : 'Create Vendor'" />
        </div>

    </x-admin-section-card>

    <livewire:admin.media-library.picker-modal key="product-vendors-form-picker-modal" />
</div>
