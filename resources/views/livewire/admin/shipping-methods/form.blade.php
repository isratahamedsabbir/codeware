<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.shipping-methods') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full max-w-2xl bg-white rounded-[5px] shadow-sm p-6 space-y-4">

        <flux:field>
            <flux:label>Name <span class="text-red-500 ml-0.5">*</span><x-field-hint text="Shown to customers as a shipping option at checkout." /></flux:label>
            <flux:input wire:model="name" placeholder="e.g. Standard Delivery, Inside Dhaka" />
            <flux:error name="name" />
        </flux:field>

        <flux:field class="max-w-xs">
            <flux:label>Cost <span class="text-red-500 ml-0.5">*</span></flux:label>
            <flux:input type="number" wire:model="cost" min="0" step="0.01" placeholder="e.g. 60" />
            <flux:error name="cost" />
        </flux:field>

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="admin-btn-save inline-flex items-center gap-2 px-5 h-8 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                <svg wire:loading.remove wire:target="save" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17 21 17 13 7 13 7 21" />
                    <polyline points="7 3 7 8 15 8" />
                </svg>
                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" stroke-opacity="0.25" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke-opacity="1" />
                </svg>
                <span wire:loading.remove wire:target="save">{{ $shippingMethodId ? 'Update Shipping Method' : 'Create Shipping Method' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>

    </div>
</div>
