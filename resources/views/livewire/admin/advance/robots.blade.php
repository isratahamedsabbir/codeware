<div class="max-w-2xl space-y-6">
    @if (session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm dark:bg-green-950 dark:border-green-800 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <flux:text class="text-zinc-500">
        Controls what search engine crawlers are allowed to access, served at <code>/robots.txt</code>.
    </flux:text>

    <x-admin-section-card icon="document-text" title="Robots.txt" icon-color="bg-amber-500/10 text-amber-600">
        @if ($updatedAt)
            <x-slot:actions>
                <span class="text-xs text-zinc-400">Last updated {{ $updatedAt }}</span>
            </x-slot:actions>
        @endif

        <flux:field>
            <flux:textarea wire:model="content" rows="10" class="font-mono text-sm" />
            <flux:error name="content" />
        </flux:field>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
                Save
            </flux:button>
            <flux:button variant="ghost" wire:click="resetToDefault">
                Reset to Default
            </flux:button>
        </div>
    </x-admin-section-card>
</div>
