<div class="max-w-2xl space-y-6">
    <x-admin-section-card icon="map" title="Sitemap" icon-color="bg-sky-500/10 text-sky-600"
        description="Built from every published page, product, category and post the active theme can serve, and regenerated on every request — there is nothing to generate and nothing to forget.">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">URLs</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $urlCount }}</div>
            </div>
            <div>
                <div class="text-zinc-400 text-xs uppercase tracking-wide mb-1">Served at</div>
                <div class="font-semibold text-zinc-800 dark:text-zinc-100 break-all">
                    <a href="{{ $sitemapUrl }}" target="_blank" class="hover:underline">{{ $sitemapUrl }}</a>
                </div>
            </div>
        </div>

        <p class="text-xs text-zinc-500 dark:text-zinc-400">
            Pages marked <em>no&nbsp;index</em> are left out, and so is any URL the
            active theme has no template for — a portfolio site advertises no
            products rather than a page full of 404s.
        </p>
    </x-admin-section-card>

    <x-admin-section-card icon="document-text" title="URLs in this sitemap" icon-color="bg-zinc-500/10 text-zinc-600">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-zinc-400">
                        <th class="py-2 pr-4 font-medium">URL</th>
                        <th class="py-2 pr-4 font-medium">Last modified</th>
                        <th class="py-2 pr-4 font-medium">Change</th>
                        <th class="py-2 font-medium">Priority</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($entries as $entry)
                        <tr>
                            <td class="py-2 pr-4 font-mono text-xs break-all">{{ $entry['loc'] }}</td>
                            <td class="py-2 pr-4 text-xs text-zinc-500 whitespace-nowrap">
                                {{ $entry['lastmod'] ? \Illuminate\Support\Carbon::parse($entry['lastmod'])->format('Y-m-d H:i') : '—' }}
                            </td>
                            <td class="py-2 pr-4 text-xs text-zinc-500">{{ $entry['changefreq'] }}</td>
                            <td class="py-2 text-xs text-zinc-500">{{ number_format($entry['priority'], 1) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin-section-card>
</div>
