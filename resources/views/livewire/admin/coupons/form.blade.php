<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.coupons') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full bg-white rounded-[5px] shadow-sm p-6 space-y-4">

        <flux:field>
            <flux:label>Code <span class="text-red-500 ml-0.5">*</span></flux:label>
            <flux:input wire:model="code" placeholder="e.g. SAVE20" class="uppercase font-mono" />
            <p class="text-xs text-zinc-400 mt-1">Customers enter this at checkout — always stored uppercase.</p>
            <flux:error name="code" />
        </flux:field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Discount Type <span class="text-red-500 ml-0.5">*</span></flux:label>
                <select wire:model="type"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount</option>
                </select>
                <flux:error name="type" />
            </flux:field>
            <flux:field>
                <flux:label>Value <span class="text-red-500 ml-0.5">*</span></flux:label>
                <flux:input type="number" step="0.01" min="0" wire:model="value"
                    placeholder="{{ $type === 'percentage' ? 'e.g. 20' : 'e.g. 500' }}" />
                <flux:error name="value" />
            </flux:field>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Minimum Order Amount</flux:label>
                <flux:input type="number" step="0.01" min="0" wire:model="min_order_amount" placeholder="No minimum" />
                <flux:error name="min_order_amount" />
            </flux:field>
            <flux:field>
                <flux:label>Max Uses</flux:label>
                <flux:input type="number" min="1" wire:model="max_uses" placeholder="Unlimited" />
                <flux:error name="max_uses" />
            </flux:field>
        </div>

        <flux:field class="max-w-xs">
            <flux:label>Expires On</flux:label>
            <flux:input type="date" wire:model="expires_at" />
            <p class="text-xs text-zinc-400 mt-1">Leave blank for a coupon that never expires.</p>
            <flux:error name="expires_at" />
        </flux:field>

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="admin-btn-save inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                <svg wire:loading.remove wire:target="save" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17 21 17 13 7 13 7 21" />
                    <polyline points="7 3 7 8 15 8" />
                </svg>
                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" stroke-opacity="0.25" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke-opacity="1" />
                </svg>
                <span wire:loading.remove wire:target="save">{{ $couponId ? 'Update Coupon' : 'Create Coupon' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>

    </div>
</div>
