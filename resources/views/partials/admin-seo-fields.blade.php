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
            {{-- These are the fields a search engine reads, and they are read
                 per language: the Bengali version of a page is served to
                 Bengali searchers, so a meta title left in English on that page
                 is a wasted impression. Same locale tabs as every other
                 translated field, so the copy is written where it is used. --}}
            <x-admin-locale-tabs>
                @foreach (\App\Support\Locale::translatable() as $language)
                    <x-admin-locale-panel :code="$language->code">
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Meta Title</flux:label>
                                <flux:input wire:model="seo_title.{{ $language->code }}"
                                    placeholder="{{ $language->code === $this->primaryLocale ? 'SEO-optimized title' : 'SEO-optimized title ('.($language->native_name ?: $language->name).')' }}" />
                                <flux:error name="seo_title.{{ $language->code }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Meta Description</flux:label>
                                <flux:textarea wire:model="seo_description.{{ $language->code }}" class="h-24"
                                    placeholder="{{ $language->code === $this->primaryLocale ? 'Brief description for search engines…' : 'Brief description for search engines ('.($language->native_name ?: $language->name).')…' }}" />
                                <flux:error name="seo_description.{{ $language->code }}" />
                            </flux:field>
                        </div>
                    </x-admin-locale-panel>
                @endforeach
            </x-admin-locale-tabs>
        </div>

        {{-- Open Graph --}}
        <div class="lg:col-span-2 min-w-0">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-4">
                <x-admin-locale-tabs>
                    @foreach (\App\Support\Locale::translatable() as $language)
                        <x-admin-locale-panel :code="$language->code">
                            <div class="space-y-4 min-w-0">
                                <flux:field>
                                    <flux:label>OG Title</flux:label>
                                    <flux:input wire:model="og_title.{{ $language->code }}" placeholder="Title shown when shared on social media" />
                                    <flux:error name="og_title.{{ $language->code }}" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>OG Description</flux:label>
                                    <flux:textarea wire:model="og_description.{{ $language->code }}" class="h-24" placeholder="Description shown when shared on social media" />
                                    <flux:error name="og_description.{{ $language->code }}" />
                                </flux:field>
                            </div>
                        </x-admin-locale-panel>
                    @endforeach
                </x-admin-locale-tabs>
                <div class="min-w-0">
                    <flux:field>
                        <flux:label>OG Image</flux:label>
                        {{-- Not translated: one image has one URL, and pointing the
                             Bengali card at a different file would just be a second
                             image to keep uploaded. --}}
                        <x-media-picker model="og_image" label="" size-hint="1200 × 630" placeholder="Select OG image from library"
                            :picker-id="$ogImagePickerId" mimes="jpg,jpeg,png,webp,avif" only-images dropzone />
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Twitter Card --}}
        <div class="lg:col-span-2 min-w-0">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-4">
                <x-admin-locale-tabs>
                    @foreach (\App\Support\Locale::translatable() as $language)
                        <x-admin-locale-panel :code="$language->code">
                            <div class="space-y-4 min-w-0">
                                <flux:field>
                                    <flux:label>Twitter Title</flux:label>
                                    <flux:input wire:model="twitter_title.{{ $language->code }}" placeholder="Title shown when shared on X/Twitter" />
                                    <flux:error name="twitter_title.{{ $language->code }}" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Twitter Description</flux:label>
                                    <flux:textarea wire:model="twitter_description.{{ $language->code }}" class="h-24" placeholder="Description shown when shared on X/Twitter" />
                                    <flux:error name="twitter_description.{{ $language->code }}" />
                                </flux:field>
                            </div>
                        </x-admin-locale-panel>
                    @endforeach
                </x-admin-locale-tabs>
                <div class="min-w-0">
                    <flux:field>
                        <flux:label>Twitter Image</flux:label>
                        <x-media-picker model="twitter_image" label="" size-hint="1200 × 630" placeholder="Select Twitter image from library"
                            :picker-id="$twitterImagePickerId" mimes="jpg,jpeg,png,webp,avif" only-images dropzone />
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
