<div class="max-w-2xl space-y-6">
    @if (session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm dark:bg-green-950 dark:border-green-800 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <flux:text class="text-zinc-500">
        Generates <code>sitemap.xml</code> from every published page, product, category and post — ready for search engines to crawl.
    </flux:text>

    <x-admin-section-card icon="map" title="Sitemap" icon-color="bg-sky-500/10 text-sky-600">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">URLs</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $urlCount ?? '—' }}</div>
            </div>
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Last generated</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $generatedAt ?? 'Never' }}</div>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-1">
            <flux:button variant="primary" wire:click="generate" wire:loading.attr="disabled">
                Generate Sitemap
            </flux:button>

            @if ($generatedAt)
                <a href="{{ url('/sitemap.xml') }}" target="_blank"
                    class="text-sm text-primary hover:underline">
                    View sitemap.xml
                </a>
            @endif
        </div>
    </x-admin-section-card>
</div>
