<x-admin-section-card icon="magnifying-glass" title="Search Engine (SEO) Settings"
    icon-color="bg-sky-500/10 text-sky-600" body-class="px-6 py-5 grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-5"
    description="Meta tags and indexing controls." collapsible :collapsed="true">

        {{-- Canonical URL --}}
        <div class="lg:col-span-2 min-w-0">
            <flux:field>
                <flux:label>Canonical URL<x-field-hint text="The base is managed in Settings → SEO → Canonical Base Links. The path defaults to the slug but can be edited independently." /></flux:label>
                <div class="flex items-center gap-2">
                    <select wire:model="canonical_base"
                        class="rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700 shrink-0">
                        <option value="">Select base…</option>
                        @foreach ($this->canonicalBaseOptions() as $base)
                            <option value="{{ $base }}">{{ $base }}</option>
                        @endforeach
                    </select>
                    <flux:input wire:model.live.debounce.400ms="canonical_slug" placeholder="page-slug" class="flex-1" />
                </div>
                <flux:error name="canonical_base" />
                <flux:error name="canonical_slug" />
            </flux:field>
        </div>

        {{-- Meta tags --}}
        <div class="lg:col-span-2 min-w-0">
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Meta Title</flux:label>
                    <flux:input wire:model="seo_title" placeholder="SEO-optimized title" />
                    <flux:error name="seo_title" />
                </flux:field>
                <flux:field>
                    <flux:label>Meta Description</flux:label>
                    <flux:textarea wire:model="seo_description" class="h-24" placeholder="Brief description for search engines…" />
                    <flux:error name="seo_description" />
                </flux:field>
            </div>
        </div>

        {{-- Open Graph --}}
        <div class="lg:col-span-2 min-w-0">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-4">
                <div class="space-y-4 min-w-0">
                    <flux:field>
                        <flux:label>OG Title</flux:label>
                        <flux:input wire:model="og_title" placeholder="Title shown when shared on social media" />
                        <flux:error name="og_title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>OG Description</flux:label>
                        <flux:textarea wire:model="og_description" class="h-24" placeholder="Description shown when shared on social media" />
                        <flux:error name="og_description" />
                    </flux:field>
                </div>
                <div class="min-w-0">
                    <flux:field>
                        <flux:label>OG Image</flux:label>
                        <flux:text class="-mt-1! mb-1 block text-[11px] text-zinc-400">1200×630px</flux:text>
                        <x-media-picker model="og_image" label="" placeholder="Select OG image from library"
                            :picker-id="$ogImagePickerId" mimes="jpg,jpeg,png,webp" only-images dropzone />
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Twitter Card --}}
        <div class="lg:col-span-2 min-w-0">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-4">
                <div class="space-y-4 min-w-0">
                    <flux:field>
                        <flux:label>Twitter Title</flux:label>
                        <flux:input wire:model="twitter_title" placeholder="Title shown when shared on X/Twitter" />
                        <flux:error name="twitter_title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Twitter Description</flux:label>
                        <flux:textarea wire:model="twitter_description" class="h-24" placeholder="Description shown when shared on X/Twitter" />
                        <flux:error name="twitter_description" />
                    </flux:field>
                </div>
                <div class="min-w-0">
                    <flux:field>
                        <flux:label>Twitter Image</flux:label>
                        <flux:text class="-mt-1! mb-1 block text-[11px] text-zinc-400">1200×630px</flux:text>
                        <x-media-picker model="twitter_image" label="" placeholder="Select Twitter image from library"
                            :picker-id="$twitterImagePickerId" mimes="jpg,jpeg,png,webp" only-images dropzone />
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Indexing --}}
        <div class="lg:col-span-2 min-w-0">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <p class="text-sm font-medium text-zinc-700">No-Index</p>
                        <x-field-hint text="Prevent search engines from indexing this" />
                    </div>
                    <flux:switch wire:model="no_index" />
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <p class="text-sm font-medium text-zinc-700">No-Follow</p>
                        <x-field-hint text="Prevent search engines from following links on this" />
                    </div>
                    <flux:switch wire:model="no_follow" />
                </div>
            </div>
        </div>
</x-admin-section-card>
