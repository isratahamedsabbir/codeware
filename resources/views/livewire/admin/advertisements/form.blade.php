<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.advertisements') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full max-w-[1100px] bg-white rounded-[5px] shadow-sm p-6">

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_420px] gap-8">

            {{-- Left: fields --}}
            <div class="space-y-3">

                @if ($code)
                    <div class="flex items-center gap-2 text-xs text-zinc-500">
                        <span>Code:</span>
                        <x-copy-text :text="$code" class="font-mono text-zinc-600">{{ $code }}</x-copy-text>
                        <span class="text-zinc-300">•</span>
                        <span>Generated automatically, cannot be edited.</span>
                    </div>
                @endif

                <flux:field>
                    <flux:label>Name <span class="text-red-500 ml-0.5">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="e.g. Summer Sale — 15% Off Everything" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Destination URL</flux:label>
                    <flux:input type="url" wire:model="url" placeholder="https://example.com/landing-page" />
                    <flux:error name="url" />
                    <x-field-hint>Where visitors land after clicking the ad. Leave blank to link to the homepage.</x-field-hint>
                </flux:field>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Valid From</flux:label>
                        <flux:input type="date" wire:model="validFrom" />
                        <flux:error name="validFrom" />
                        <x-field-hint>Leave blank to go live immediately / always.</x-field-hint>
                    </flux:field>

                    <flux:field>
                        <flux:label>Valid Until</flux:label>
                        <flux:input type="date" wire:model="validUntil" />
                        <flux:error name="validUntil" />
                        <x-field-hint>Leave blank to run indefinitely.</x-field-hint>
                    </flux:field>
                </div>

                @if ($validFrom !== '' && $validUntil !== '' && $validUntil < $validFrom)
                    <p class="text-xs text-red-600">The end date must be on or after the start date.</p>
                @endif

            </div>

            {{-- Right: image --}}
            <div>
                <x-media-picker model="image" label="Advertisement Image" size-hint="Recommended profile 600 × 750" placeholder="Select an image from the library" mimes="jpg,jpeg,png,webp,avif,svg" only-images dropzone />
            </div>

        </div>

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$advertisementId ? 'Update Advertisement' : 'Create Advertisement'" />
        </div>

    </div>
</div>