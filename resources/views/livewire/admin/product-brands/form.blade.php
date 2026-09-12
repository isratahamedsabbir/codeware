<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.product-brands') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full max-w-2xl bg-white rounded-[5px] shadow-sm p-6 space-y-4">

        <flux:field>
            <flux:label>Name <span class="text-red-500 ml-0.5">*</span></flux:label>
            <flux:input wire:model="name" placeholder="e.g. Nestle, Unilever, Samsung" />
            <flux:error name="name" />
        </flux:field>

        <x-media-picker model="logo" label="Logo" hint="Square image works best" placeholder="Select brand logo from library" mimes="jpg,jpeg,png,webp,svg" only-images dropzone />

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$brandId ? 'Update Brand' : 'Create Brand'" />
        </div>

    </div>
</div>
