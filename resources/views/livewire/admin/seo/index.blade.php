<div class="max-w-[1600px] space-y-6">
    <flux:text class="text-zinc-500">
        Control search engine visibility and social sharing metadata for your site.
    </flux:text>

    <div class="space-y-4">

    {{-- Meta tags --}}
    <div class="rounded-xl bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/40 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
            <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
                <flux:icon.document-text class="size-5" />
            </div>
            <div>
                <flux:heading size="sm">Meta Tags</flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    What search engines like Google show in results.
                </flux:text>
            </div>
        </div>

        <div class="px-6 py-5 space-y-4">
            <flux:field>
                @include('partials.seo-char-counter', ['field' => 'seo_meta_title', 'max' => 60, 'label' => 'Meta Title'])
                <flux:input wire:model="settings.seo_meta_title"
                    placeholder="Title shown in search engine results" />
            </flux:field>
            <flux:field>
                @include('partials.seo-char-counter', ['field' => 'seo_meta_description', 'max' => 160, 'label' => 'Meta Description'])
                <flux:textarea wire:model="settings.seo_meta_description" class="h-48"
                    placeholder="Short summary shown in search engine results" />
            </flux:field>
        </div>
    </div>

    {{-- Open Graph --}}
    <div class="rounded-xl bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/40 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
            <div class="flex size-9 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 shrink-0">
                <flux:icon.share class="size-5" />
            </div>
            <div>
                <flux:heading size="sm">Open Graph</flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    Used when your site is shared on social media (Facebook, WhatsApp, etc.).
                </flux:text>
            </div>
        </div>

        <div class="px-6 py-5 space-y-4">
            <flux:field>
                @include('partials.seo-char-counter', ['field' => 'seo_og_title', 'max' => 70, 'label' => 'OG Title'])
                <flux:input wire:model="settings.seo_og_title"
                    placeholder="Defaults to Meta Title if left blank" />
            </flux:field>
            <flux:field>
                @include('partials.seo-char-counter', ['field' => 'seo_og_description', 'max' => 200, 'label' => 'OG Description'])
                <flux:textarea wire:model="settings.seo_og_description" class="h-48"
                    placeholder="Defaults to Meta Description if left blank" />
            </flux:field>
            <x-media-picker model="settings.seo_og_image" label="OG Image"
                placeholder="Recommended size: 1200 × 630px" dropzone />
        </div>
    </div>

    {{-- Twitter Card --}}
    <div class="rounded-xl bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/40 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
            <div class="flex size-9 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 shrink-0">
                <flux:icon.at-symbol class="size-5" />
            </div>
            <div>
                <flux:heading size="sm">Twitter Card</flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    Used when your site is shared on Twitter / X.
                </flux:text>
            </div>
        </div>

        <div class="px-6 py-5 space-y-4">
            <flux:field>
                <flux:label>Card Type</flux:label>
                <select wire:model="settings.seo_twitter_card"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                    <option value="summary_large_image">Summary with large image</option>
                    <option value="summary">Summary</option>
                </select>
            </flux:field>
            <flux:field>
                <flux:label>Twitter @username</flux:label>
                <flux:input wire:model="settings.seo_twitter_site" placeholder="@yoursite" />
            </flux:field>
            <flux:field>
                @include('partials.seo-char-counter', ['field' => 'seo_twitter_title', 'max' => 70, 'label' => 'Twitter Title'])
                <flux:input wire:model="settings.seo_twitter_title"
                    placeholder="Defaults to Meta Title if left blank" />
            </flux:field>
            <flux:field>
                @include('partials.seo-char-counter', ['field' => 'seo_twitter_description', 'max' => 200, 'label' => 'Twitter Description'])
                <flux:textarea wire:model="settings.seo_twitter_description" class="h-48"
                    placeholder="Defaults to Meta Description if left blank" />
            </flux:field>
            <x-media-picker model="settings.seo_twitter_image" label="Twitter Image"
                placeholder="Recommended size: 1200 × 675px" dropzone />
        </div>
    </div>

    </div>

    {{-- Canonical base links --}}
    <div class="rounded-xl bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/40 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
            <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 shrink-0">
                <flux:icon.link class="size-5" />
            </div>
            <div>
                <flux:heading size="sm">Canonical Base Links</flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    Base URLs used to build canonical link tags. Add one for every domain your site is reachable on.
                </flux:text>
            </div>
        </div>

        <div class="px-6 py-5 space-y-4">
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
    </div>

    <div>
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            Save Settings
        </flux:button>
    </div>

    <livewire:admin.media-library.picker-modal key="seo-picker-modal" />
</div>
