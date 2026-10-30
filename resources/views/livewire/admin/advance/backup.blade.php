<div class="max-w-2xl space-y-6">
    <x-admin-section-card icon="archive-box" title="Backup" icon-color="bg-emerald-500/10 text-emerald-600"
        description="Download everything as one zip (database dump + files), or just the database (.sql) or just the files (storage/app) separately. A full backup contains the live database dump (schema + data) and everything under storage/app — uploaded media, generated files and .env backups — as a single snapshot.">
        <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Database</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $connectionName }}</div>
            </div>
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Tables</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $tableCount }}</div>
            </div>
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Files</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $fileCount }}</div>
            </div>
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Size</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $dbSizeMb }} MB DB · {{ $fileSizeMb }} MB files</div>
            </div>
        </div>

        <div class="pt-1 flex flex-wrap gap-2">
            @foreach ([
                ['full', 'archive-box-arrow-down', 'Full Backup (.zip)', 'primary'],
                ['database', 'circle-stack', 'Database only (.sql)', 'filled'],
                ['files', 'folder-arrow-down', 'Files only (.zip)', 'filled'],
            ] as [$type, $icon, $label, $variant])
                <flux:button size="sm" :variant="$variant" :icon="$icon" wire:click="download('{{ $type }}')" wire:loading.attr="disabled" wire:target="download">
                    <span wire:loading.remove wire:target="download('{{ $type }}')">{{ $label }}</span>
                    <span wire:loading wire:target="download('{{ $type }}')">Preparing…</span>
                </flux:button>
            @endforeach
        </div>
    </x-admin-section-card>
</div>