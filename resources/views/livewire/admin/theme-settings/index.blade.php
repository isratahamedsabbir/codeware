<div class="max-w-[1600px] space-y-5">
    <div class="space-y-5">

        {{-- Site Design --}}
        <x-admin-section-card header-border="border-zinc-100" icon="swatch" title="Site Design"
            description="The design shown to visitors on the public site — pick one, then save to apply. Theme-specific content is set below.">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($themes as $slug => $label)
                    <label class="relative flex cursor-pointer flex-col rounded-xl border p-5 transition
                        {{ ($settings['site_theme'] ?? null) === $slug
                            ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                            : 'border-zinc-300 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-500' }}">

                        <input type="radio" name="site_theme" value="{{ $slug }}"
                            wire:model.live="settings.site_theme" class="sr-only">

                        <div class="flex items-center justify-between gap-2">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500">
                                <flux:icon.paint-brush class="h-5 w-5" />
                            </span>
                            @if ($slug === $activeTheme)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold tracking-wide text-emerald-700 uppercase">
                                    Live
                                </span>
                            @elseif (($settings['site_theme'] ?? null) === $slug)
                                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold tracking-wide text-primary uppercase">
                                    Selected
                                </span>
                            @endif
                        </div>

                        <span class="mt-3 text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $label }}</span>
                        <span class="mt-0.5 text-xs text-zinc-400">{{ $slug }}</span>

                        <span class="mt-3 hidden text-xs font-semibold text-primary sm:block"
                            x-show="$wire.settings.site_theme === '{{ $slug }}'" x-cloak>
                            &check; Selected
                        </span>
                    </label>
                @endforeach
            </div>

            <p class="mt-4 text-xs text-zinc-400">
                Currently live: <span class="font-semibold text-zinc-600 dark:text-zinc-300">{{ $themes[$activeTheme] ?? $activeTheme }}</span>.
                Your changes apply to the public site once you save.
            </p>
        </x-admin-section-card>

        {{-- Homepage --}}
        <x-admin-section-card header-border="border-zinc-100" icon="home" title="Homepage"
            description="Copy and imagery the homepage of your theme renders on the public site.">
            <div class="space-y-5">

                <flux:field>
                    <flux:label>Site Tagline<x-field-hint text="A short line under the site name on the homepage." /></flux:label>
                    <flux:textarea wire:model="settings.site_tagline" class="h-20" placeholder="e.g. Your one-stop shop for everything" />
                </flux:field>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <x-media-picker model="settings.home_hero_image" label="Hero Image"
                        hint="Large banner at the top of the homepage"
                        placeholder="Choose a hero image from the library" only-images mimes="jpg,jpeg,png,gif,webp" :max-size-mb="4" />

                    <x-media-picker model="settings.home_promo_banner_1" label="Promo Banner 1"
                        hint="First promotional banner below the hero"
                        placeholder="Choose an image from the library" only-images mimes="jpg,jpeg,png,gif,webp" :max-size-mb="4" />

                    <x-media-picker model="settings.home_promo_banner_2" label="Promo Banner 2"
                        hint="Second promotional banner below the hero"
                        placeholder="Choose an image from the library" only-images mimes="jpg,jpeg,png,gif,webp" :max-size-mb="4" />
                </div>
            </div>
        </x-admin-section-card>

        {{-- Extras --}}
        <x-admin-section-card header-border="border-zinc-100" icon="globe-alt" title="Extras">
            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 cursor-pointer">
                <input type="checkbox" wire:model="settings.chat_widget_enabled" class="rounded border-zinc-300 text-primary" />
                Chat Box
            </label>
            <p class="text-xs text-zinc-400 mt-1">
                Shows the live support chat bubble in the corner of every public page.
            </p>
        </x-admin-section-card>

        {{-- Save --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.settings') }}" class="text-sm text-zinc-500 hover:text-zinc-700">Back to Settings</a>
            <flux:button variant="primary" type="button" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Theme Settings</span>
                <span wire:loading>Saving…</span>
            </flux:button>
        </div>

    </div>
</div>