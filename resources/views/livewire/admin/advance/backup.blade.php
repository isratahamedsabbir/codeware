<div class="max-w-2xl space-y-6">
    <flux:text class="text-zinc-500">
        Downloads everything under <code>storage/app</code> — uploaded media, generated files and .env backups — as a single zip.
    </flux:text>

    <x-admin-section-card icon="archive-box" title="Storage" icon-color="bg-emerald-500/10 text-emerald-600">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Files</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $fileCount }}</div>
            </div>
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Size</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $sizeMb }} MB</div>
            </div>
        </div>

        <div class="pt-1">
            <flux:button variant="primary" icon="archive-box-arrow-down" wire:click="download" wire:loading.attr="disabled" wire:target="download">
                <span wire:loading.remove wire:target="download">Download Storage Backup (.zip)</span>
                <span wire:loading wire:target="download">Zipping files…</span>
            </flux:button>
        </div>
    </x-admin-section-card>
</div>
