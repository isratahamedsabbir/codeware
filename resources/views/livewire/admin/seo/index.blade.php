<div class="max-w-[1600px] space-y-6">
    <flux:text class="text-zinc-500">
        Control search engine visibility and social sharing metadata for your site.
    </flux:text>

    <div class="space-y-4">

    {{-- Meta tags --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
        <flux:heading size="sm">Meta Tags</flux:heading>
        <flux:field>
            <flux:label>Meta Title</flux:label>
            <flux:input wire:model="settings.seo_meta_title"
                placeholder="Title shown in search engine results" />
        </flux:field>
        <flux:field>
            <flux:label>Meta Description</flux:label>
            <flux:textarea wire:model="settings.seo_meta_description" class="h-48"
                placeholder="Short summary shown in search engine results" />
        </flux:field>
    </div>

    {{-- Open Graph --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
        <flux:heading size="sm">Open Graph</flux:heading>
        <p class="mt-0.5 text-xs text-zinc-400">
            Used when your site is shared on social media (Facebook, WhatsApp, etc.).
        </p>
        <flux:field>
            <flux:label>OG Title</flux:label>
            <flux:input wire:model="settings.seo_og_title"
                placeholder="Title shown when shared on social media" />
        </flux:field>
        <flux:field>
            <flux:label>OG Description</flux:label>
            <flux:textarea wire:model="settings.seo_og_description" class="h-48"
                placeholder="Description shown when shared on social media" />
        </flux:field>
        <x-media-picker model="settings.seo_og_image" label="OG Image"
            placeholder="Select OG image from library" dropzone />
    </div>

    </div>

    {{-- Canonical base links --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
        <flux:heading size="sm">Canonical Base Links</flux:heading>
        <p class="mt-0.5 text-xs text-zinc-400">
            Base URLs used to build canonical link tags. Add one for every domain your site is reachable on.
        </p>
        <div class="space-y-3 max-w-2xl">
            @foreach ($canonicalUrls as $index => $url)
                <div class="flex items-center gap-2">
                    <flux:input wire:model="canonicalUrls.{{ $index }}" placeholder="https://example.com"
                        class="flex-1" />
                    <flux:button variant="subtle" square icon="trash"
                        wire:click="removeCanonicalUrl({{ $index }})" aria-label="Remove link" />
                </div>
            @endforeach
        </div>
        <flux:button variant="ghost" icon="plus" wire:click="addCanonicalUrl">
            Add Link
        </flux:button>
    </div>

    <div>
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            Save Settings
        </flux:button>
    </div>

    <livewire:admin.media-library.picker-modal key="seo-picker-modal" />
</div>
