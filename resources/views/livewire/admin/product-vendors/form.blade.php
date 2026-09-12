<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.product-vendors') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full max-w-2xl bg-white rounded-[5px] shadow-sm p-6 space-y-4">

        <flux:field>
            <flux:label>Name <span class="text-red-500 ml-0.5">*</span></flux:label>
            <flux:input wire:model="name" placeholder="e.g. Global Supplies Ltd." />
            <flux:error name="name" />
        </flux:field>

        <x-media-picker model="logo" label="Logo" hint="Square image works best" placeholder="Select vendor logo from library" mimes="jpg,jpeg,png,webp,svg" only-images dropzone />

        <flux:field>
            <flux:label>Address</flux:label>
            <flux:textarea wire:model="address" rows="3" placeholder="Vendor's business address" />
            <flux:error name="address" />
        </flux:field>

        <flux:field>
            <flux:label>Assigned Users<x-field-hint text="These users can log in and see this vendor's products and orders in the Vendor Portal. A user can be assigned to more than one vendor." /></flux:label>
            <flux:checkbox.group wire:model="user_ids" class="flex-col items-stretch gap-0.5 max-h-64 overflow-y-auto border border-zinc-200 rounded-lg p-2">
                @forelse ($users as $user)
                    <div class="rounded-md py-1 px-1 hover:bg-zinc-50 transition-colors">
                        <flux:checkbox value="{{ $user->id }}" label="{{ $user->name }} ({{ $user->email }})" />
                    </div>
                @empty
                    <p class="text-xs text-zinc-400 px-2 py-1">No users yet.</p>
                @endforelse
            </flux:checkbox.group>
            <flux:error name="user_ids" />
        </flux:field>

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$vendorId ? 'Update Vendor' : 'Create Vendor'" />
        </div>

    </div>
</div>
