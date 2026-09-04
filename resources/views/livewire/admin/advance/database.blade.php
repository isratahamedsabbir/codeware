<div class="max-w-2xl space-y-6">
    <flux:text class="text-zinc-500">
        Downloads a full SQL dump (schema + data) of the live database — useful before a risky migration or as an off-site backup.
    </flux:text>

    <x-admin-section-card icon="circle-stack" :title="$connectionName" icon-color="bg-indigo-500/10 text-indigo-600">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Tables</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $tableCount }}</div>
            </div>
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Size</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $sizeMb }} MB</div>
            </div>
        </div>

        <div class="pt-1">
            <flux:button variant="primary" icon="arrow-down-tray" wire:click="download" wire:loading.attr="disabled" wire:target="download">
                <span wire:loading.remove wire:target="download">Download Database (.sql)</span>
                <span wire:loading wire:target="download">Preparing dump…</span>
            </flux:button>
        </div>
    </x-admin-section-card>
</div>
