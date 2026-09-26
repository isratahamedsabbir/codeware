<div class="max-w-[1600px] space-y-5">
    <div class="space-y-5">

    <x-admin-section-card icon="magnifying-glass" title="Search Engine (SEO) Settings"
        icon-color="bg-sky-500/10 text-sky-600" body-class="px-6 py-5"
        description="What search engines show in results, and what appears when your site is shared on social media."
        collapsible :collapsed="true">

        {{-- These are the fallbacks every page without its own SEO copy inherits,
             and they are read in the reader's language: a Bengali visitor is
             served a Bengali page, so an English meta description underneath it
             is a wasted impression. One strip for the whole section, same as the
             SEO block on the Page form, so a switch moves the meta, Open Graph
             and Twitter copy together instead of one toggle per block. --}}
        <x-admin-locale-tabs>
            @php($locales = \App\Support\Locale::translatable())

            <div class="space-y-5">
                {{-- Meta tags --}}
                @foreach ($locales as $language)
                    <x-admin-locale-panel :code="$language->code" class="space-y-4 min-w-0">
                        <flux:field>
                            @include('partials.seo-char-counter', [
                                'path' => 'settings.seo_meta_title.'.$language->code,
                                'max' => 60,
                                'label' => 'Meta Title',
                                'locale' => $language->code,
                            ])
                            <flux:input wire:model="settings.seo_meta_title.{{ $language->code }}"
                                placeholder="Title shown in search engine results" />
                        </flux:field>
                        <flux:field>
                            @include('partials.seo-char-counter', [
                                'path' => 'settings.seo_meta_description.'.$language->code,
                                'max' => 160,
                                'label' => 'Meta Description',
                                'locale' => $language->code,
                            ])
                            <flux:textarea wire:model="settings.seo_meta_description.{{ $language->code }}" class="h-24"
                                placeholder="Short summary shown in search engine results" />
                        </flux:field>
                    </x-admin-locale-panel>
                @endforeach

                {{-- Open Graph --}}
                <div class="min-w-0">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-4">
                        @foreach ($locales as $language)
                            <x-admin-locale-panel :code="$language->code" class="space-y-4 min-w-0">
                                <flux:field>
                                    @include('partials.seo-char-counter', [
                                        'path' => 'settings.seo_og_title.'.$language->code,
                                        'max' => 70,
                                        'label' => 'OG Title',
                                        'locale' => $language->code,
                                    ])
                                    <flux:input wire:model="settings.seo_og_title.{{ $language->code }}"
                                        placeholder="Defaults to Meta Title if left blank" />
                                </flux:field>
                                <flux:field>
                                    @include('partials.seo-char-counter', [
                                        'path' => 'settings.seo_og_description.'.$language->code,
                                        'max' => 200,
                                        'label' => 'OG Description',
                                        'locale' => $language->code,
                                    ])
                                    <flux:textarea wire:model="settings.seo_og_description.{{ $language->code }}" class="h-24"
                                        placeholder="Defaults to Meta Description if left blank" />
                                </flux:field>
                            </x-admin-locale-panel>
                        @endforeach
                        <div class="min-w-0">
                            <flux:field>
                                <flux:label>OG Image</flux:label>
                                <flux:text class="-mt-1! mb-1 block text-[11px] text-zinc-400">1200×630px</flux:text>
                                {{-- Not translated: one image has one URL, and pointing the
                                     Bengali card at a different file would just be a second
                                     image to keep uploaded. --}}
                                <x-media-picker model="settings.seo_og_image" label=""
                                    placeholder="Select OG image from library" mimes="jpg,jpeg,png,webp,avif" only-images dropzone />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Twitter Card --}}
                <div class="min-w-0">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-4">
                        <div class="space-y-4 min-w-0">
                            {{-- No card type and no @username input: the seeded card
                                 type is already summary_large_image, so a site with
                                 a Twitter image gets the large card without being
                                 told to, and the site handle belongs to the social
                                 account rather than to the SEO copy. Both settings
                                 stay in the database, so save() still round-trips
                                 whatever they were already set to. --}}
                            @foreach ($locales as $language)
                                <x-admin-locale-panel :code="$language->code" class="space-y-4 min-w-0">
                                    <flux:field>
                                        @include('partials.seo-char-counter', [
                                            'path' => 'settings.seo_twitter_title.'.$language->code,
                                            'max' => 70,
                                            'label' => 'Twitter Title',
                                            'locale' => $language->code,
                                        ])
                                        <flux:input wire:model="settings.seo_twitter_title.{{ $language->code }}"
                                            placeholder="Defaults to Meta Title if left blank" />
                                    </flux:field>
                                    <flux:field>
                                        @include('partials.seo-char-counter', [
                                            'path' => 'settings.seo_twitter_description.'.$language->code,
                                            'max' => 200,
                                            'label' => 'Twitter Description',
                                            'locale' => $language->code,
                                        ])
                                        <flux:textarea wire:model="settings.seo_twitter_description.{{ $language->code }}" class="h-24"
                                            placeholder="Defaults to Meta Description if left blank" />
                                    </flux:field>
                                </x-admin-locale-panel>
                            @endforeach
                        </div>
                        <div class="min-w-0">
                            <flux:field>
                                <flux:label>Twitter Image</flux:label>
                                <flux:text class="-mt-1! mb-1 block text-[11px] text-zinc-400">1200×675px</flux:text>
                                <x-media-picker model="settings.seo_twitter_image" label=""
                                    placeholder="Select Twitter image from library" mimes="jpg,jpeg,png,webp,avif" only-images dropzone />
                            </flux:field>
                        </div>
                    </div>
                </div>
            </div>
        </x-admin-locale-tabs>
    </x-admin-section-card>

    </div>

    <x-admin-section-card icon="link" title="Canonical Base Links" icon-color="bg-emerald-500/10 text-emerald-600"
        description="Base URLs used to build canonical link tags. Add one for every domain your site is reachable on.">
        <div class="space-y-3 max-w-2xl">
            @foreach ($canonicalUrls as $index => $url)
                <div class="flex items-center gap-2">
                    <flux:input wire:model="canonicalUrls.{{ $index }}" placeholder="https://example.com"
                        class="flex-1" />
                    <flux:button size="sm" variant="subtle" square icon="trash"
                        wire:click="removeCanonicalUrl({{ $index }})" aria-label="Remove link" />
                </div>
            @endforeach
        </div>
        <flux:button size="sm" variant="ghost" icon="plus" wire:click="addCanonicalUrl">
            Add Link
        </flux:button>
    </x-admin-section-card>

    <div>
        <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
            Save Settings
        </flux:button>
    </div>

    <livewire:admin.media-library.picker-modal key="seo-picker-modal" />
</div>
