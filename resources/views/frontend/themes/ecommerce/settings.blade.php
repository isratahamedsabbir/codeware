{{--
    Ecommerce theme settings — bound to the Theme Settings screen (Admin →
    Theme Settings) via wire:model="settings.*". Values persist to the settings
    table and are read by ecommerce/home.blade.php and partials/head.blade.php.

    The homepage banner uploads are laid out like the storefront's top banner
    row — the hero on the left, the two promo tiles stacked on the right — so
    it's obvious which upload lands where; each box keeps a label chip even
    once an image is set.
--}}
@php
    // The promo link field sits along the bottom of its banner box — clicks on
    // it stay in the input (it's layered above the picker button, not inside it).
    $bannerLink = 'w-full rounded-md border !border-white/25 bg-zinc-900/70 py-1.5 pl-8 pr-3 text-xs font-medium text-white shadow-sm outline-none backdrop-blur placeholder:text-white/50 focus:!border-white/70';
    $bannerChip = 'pointer-events-none absolute left-3 top-3 z-10 inline-flex items-center gap-1.5 rounded-md bg-zinc-900/75 px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm backdrop-blur';
@endphp

{{-- Tabs: one section at a time. Both stay mounted (x-show) so every media
     picker keeps its Livewire binding; the last tab is remembered per browser. --}}
<div
    x-data="{
        tab: (() => { try { return localStorage.getItem('theme-ecommerce-tab') || 'banners' } catch (e) { return 'banners' } })(),
        open(name) { this.tab = name; try { localStorage.setItem('theme-ecommerce-tab', name) } catch (e) {} },
    }"
    class="space-y-5"
>
    <div role="tablist" class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        @foreach (['banners' => ['Banners', 'photo'], 'colors' => ['Colors', 'swatch']] as $tabKey => [$tabLabel, $tabIcon])
            <button type="button" role="tab" @click="open('{{ $tabKey }}')"
                :aria-selected="tab === '{{ $tabKey }}'"
                :class="tab === '{{ $tabKey }}'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:hover:text-zinc-200'"
                class="-mb-px inline-flex items-center gap-2 rounded-none! border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors">
                <flux:icon :name="$tabIcon" variant="mini" class="size-4" />
                {{ $tabLabel }}
            </button>
        @endforeach
    </div>

    <div role="tabpanel" x-show="tab === 'banners'" x-data="{ activeSlide: 0 }">
        <div class="grid grid-cols-1 gap-4 lg:h-[26rem] lg:grid-cols-3 lg:grid-rows-2">
            {{-- Hero slider — one upload box per slide; the tabs along the bottom
                 switch between slides, add one, or remove one. Every picker stays
                 mounted (only the active one is shown) so each keeps its binding. --}}
            <div class="relative lg:col-span-2 lg:row-span-2">
                @foreach ($heroSlides as $i => $slide)
                    <div wire:key="hero-slide-{{ $i }}" x-show="activeSlide === {{ $i }}" @if ($i > 0) x-cloak @endif>
                        <x-media-picker model="heroSlides.{{ $i }}.image" label="Hero slide {{ $i + 1 }}" size-hint="1920 × 600" preview dropzone drop-height="h-64 lg:h-[26rem]"
                            only-images mimes="jpg,jpeg,png,gif,webp" :max-size-mb="4" placeholder="Choose from the library" />
                    </div>
                @endforeach

                <span class="{{ $bannerChip }}">
                    <flux:icon.photo variant="micro" class="size-3.5" />
                    Hero slider <span class="font-normal text-white/60">· <span x-text="activeSlide + 1"></span> / {{ count($heroSlides) }} · 1920 × 600</span>
                </span>

                {{-- Slide tabs --}}
                <div class="absolute inset-x-3 bottom-3 z-10 flex items-center gap-2 overflow-x-auto rounded-lg bg-zinc-900/60 p-1.5 backdrop-blur">
                    @foreach ($heroSlides as $i => $slide)
                        <div wire:key="hero-tab-{{ $i }}" class="group/tab relative shrink-0">
                            <button type="button" @click="activeSlide = {{ $i }}"
                                :class="activeSlide === {{ $i }} ? 'ring-2 ring-white' : 'opacity-70 hover:opacity-100'"
                                class="flex h-11 w-16 items-center justify-center overflow-hidden rounded-md bg-white/15 text-xs font-bold text-white transition"
                                aria-label="Slide {{ $i + 1 }}">
                                @if (filled($slide['image']))
                                    <img src="{{ $slide['image'] }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </button>
                            @if (count($heroSlides) > 1)
                                <button type="button" title="Remove slide {{ $i + 1 }}"
                                    wire:click="removeHeroSlide({{ $i }})"
                                    @click="activeSlide = Math.max(0, Math.min(activeSlide, {{ count($heroSlides) - 2 }}))"
                                    class="absolute -right-1.5 -top-1.5 hidden h-5 w-5 items-center justify-center rounded-full bg-white text-red-500 shadow group-hover/tab:flex">
                                    <flux:icon.x-mark variant="micro" class="size-3" />
                                </button>
                            @endif
                        </div>
                    @endforeach

                    @if (count($heroSlides) < \App\Livewire\Admin\ThemeSettings\Index::MAX_HERO_SLIDES)
                        <button type="button" wire:click="addHeroSlide" @click="activeSlide = {{ count($heroSlides) }}"
                            class="flex h-11 shrink-0 items-center gap-1 rounded-md border border-dashed border-white/50 px-3 text-xs font-semibold text-white transition hover:border-white hover:bg-white/10">
                            <flux:icon.plus variant="micro" class="size-3.5" />
                            Add slide
                        </button>
                    @endif
                </div>
            </div>

            {{-- Promo 1 — "New arrivals" tile --}}
            <div class="relative">
                <x-media-picker model="settings.home_promo_banner_1" label="New arrivals" size-hint="1200 × 400" preview dropzone drop-height="h-44 lg:h-[12.5rem]"
                    only-images mimes="jpg,jpeg,png,gif,webp" :max-size-mb="4" placeholder="Choose from the library" />
                <span class="{{ $bannerChip }}">
                    <flux:icon.photo variant="micro" class="size-3.5" />
                    Promo · New arrivals <span class="font-normal text-white/60">· 1200 × 400</span>
                </span>
                <label class="absolute inset-x-3 bottom-3 z-10 block" title="Where this banner links to — a page on this site (e.g. /shop?sort=newest) or a full https:// address. Blank opens the Shop page.">
                    <span class="sr-only">New arrivals link</span>
                    <flux:icon.link variant="micro" class="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-white/70" />
                    <input type="text" wire:model="settings.theme_ecommerce_promo_1_link" placeholder="Link — e.g. /shop?sort=newest (blank = Shop)"
                        class="{{ $bannerLink }}">
                </label>
            </div>

            {{-- Promo 2 — "Best deals" tile --}}
            <div class="relative">
                <x-media-picker model="settings.home_promo_banner_2" label="Best deals" size-hint="1200 × 400" preview dropzone drop-height="h-44 lg:h-[12.5rem]"
                    only-images mimes="jpg,jpeg,png,gif,webp" :max-size-mb="4" placeholder="Choose from the library" />
                <span class="{{ $bannerChip }}">
                    <flux:icon.photo variant="micro" class="size-3.5" />
                    Promo · Best deals <span class="font-normal text-white/60">· 1200 × 400</span>
                </span>
                <label class="absolute inset-x-3 bottom-3 z-10 block" title="Where this banner links to — a page on this site (e.g. /shop?sort=newest) or a full https:// address. Blank opens the Shop page.">
                    <span class="sr-only">Best deals link</span>
                    <flux:icon.link variant="micro" class="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-white/70" />
                    <input type="text" wire:model="settings.theme_ecommerce_promo_2_link" placeholder="Link — e.g. /shop?sort=newest (blank = Shop)"
                        class="{{ $bannerLink }}">
                </label>
            </div>

        {{-- The selected slide's text and link (follows the slide tabs above). --}}
        <div class="mt-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50/70 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800/50">
                <span class="flex items-center gap-2 text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                    <flux:icon.document-text variant="mini" class="size-4 text-zinc-400" />
                    Slide <span x-text="activeSlide + 1"></span> content
                </span>
                <span class="text-[11px] text-zinc-400">Shown over the slide on the homepage — all optional</span>
            </div>
            @foreach ($heroSlides as $i => $slide)
                <div wire:key="hero-content-{{ $i }}" x-show="activeSlide === {{ $i }}" @if ($i > 0) x-cloak @endif
                    class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-2">
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Title</flux:label>
                            <flux:input wire:model="heroSlides.{{ $i }}.title" placeholder="e.g. Fresh organic tea" maxlength="120" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Link<x-field-hint text="A page on this site (e.g. /shop?category=tea) or a full https:// address. Blank opens the Shop page." /></flux:label>
                            <flux:input wire:model="heroSlides.{{ $i }}.link" icon="link" placeholder="/shop" />
                        </flux:field>
                    </div>
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="heroSlides.{{ $i }}.description" rows="4" class="resize-none"
                            placeholder="e.g. Hand-picked leaves, delivered fresh to your door." maxlength="300" />
                    </flux:field>
                </div>
            @endforeach
        </div>
        </div>
    </div>
    {{-- Colors — one per storefront area. Blank means "use the default"
         (partials/head.blade.php turns each set value into the matching
         --color-* CSS variable). The mini storefront on the right previews
         the current values live, before saving. --}}
    @php
        // [key, label, default, hint] — keys are written out in full so the
        // Theme Settings component discovers them (see scopedThemeKeys()).
        $colorGroups = [
            'Header' => ['icon' => 'bars-3-bottom-left', 'fields' => [
                ['theme_ecommerce_header_bg_color', 'Background', '#045b30', 'Logo, search and account bar'],
                ['theme_ecommerce_header_text_color', 'Text & icons', '#ffffff', 'Site name, phone, icons'],
            ]],
            'Menu bar' => ['icon' => 'bars-3', 'fields' => [
                ['theme_ecommerce_nav_bg_color', 'Background', '#ffffff', 'Categories / Brands / pages row'],
                ['theme_ecommerce_nav_text_color', 'Links', '#222222', 'Menu link text'],
            ]],
            'Buttons' => ['icon' => 'cursor-arrow-rays', 'fields' => [
                ['theme_ecommerce_button_bg_color', 'Background', '#045b30', 'Add to cart, Checkout, Place order'],
                ['theme_ecommerce_button_text_color', 'Text', '#ffffff', 'Button labels'],
            ]],
            'Text' => ['icon' => 'language', 'fields' => [
                ['theme_ecommerce_heading_color', 'Headings', '#171717', 'Titles, product names, totals'],
                ['theme_ecommerce_text_color', 'Body text', '#262626', 'Regular copy and labels'],
                ['theme_ecommerce_price_color', 'Prices', '#045b30', 'Prices on cards and product page'],
            ]],
            'Accents' => ['icon' => 'sparkles', 'fields' => [
                ['theme_ecommerce_accent_color', 'Links & highlights', '#045b30', 'Links, badges, active states'],
                ['theme_ecommerce_sale_color', 'Sale badge', '#c01616', 'The "-30%" discount badge'],
                ['theme_ecommerce_page_bg_color', 'Page background', '#f2f4f8', 'Behind every storefront page'],
            ]],
            'Footer' => ['icon' => 'window', 'fields' => [
                ['theme_ecommerce_footer_bg_color', 'Background', '#045b30', 'Main footer area'],
                ['theme_ecommerce_footer_text_color', 'Text', '#ffffff', 'Headings, links, contact details'],
                ['theme_ecommerce_footer_bottom_color', 'Copyright bar', '#1d2327', 'Thin strip at the very bottom'],
            ]],
        ];
    @endphp

    <div
        x-data="{
            // Current value of a color, or its default. Areas that default to
            // the accent follow it, exactly like the storefront CSS does.
            c(key, fallback) {
                const v = ($wire.settings[key] || '').trim();
                return /^#[0-9a-fA-F]{3,8}$/.test(v) ? v : fallback;
            },
            get accent() { return this.c('theme_ecommerce_accent_color', this.c('theme_ecommerce_primary_color', '#045b30')); },
        }"
        role="tabpanel" x-show="tab === 'colors'" x-cloak
    >
        <p class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">Leave a color on its default to follow the brand accent.</p>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_19rem] xl:items-start">
            {{-- Color groups --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($colorGroups as $group => $meta)
                    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                        <header class="flex items-center justify-between gap-3 border-b border-zinc-100 bg-zinc-50/70 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <span class="flex items-center gap-2 text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                <flux:icon :name="$meta['icon']" variant="mini" class="size-4 text-zinc-400" />
                                {{ $group }}
                            </span>
                            {{-- The group's palette at a glance --}}
                            <span class="flex -space-x-1">
                                @foreach ($meta['fields'] as [$key, , $default])
                                    <span class="size-4 rounded-full ring-2 ring-white dark:ring-zinc-800"
                                        :style="`background: ${c('{{ $key }}', '{{ $default }}')}`"></span>
                                @endforeach
                            </span>
                        </header>

                        <div class="divide-y divide-zinc-100 px-4 dark:divide-zinc-800">
                            @foreach ($meta['fields'] as [$key, $label, $default, $hint])
                                <div class="flex items-center justify-between gap-3 py-2.5">
                                    <label for="{{ $key }}" class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $label }}</span>
                                        <span class="block truncate text-[11px] text-zinc-400">{{ $hint }}</span>
                                    </label>

                                    <div class="flex h-9 w-36 shrink-0 items-center gap-2 rounded-lg border border-zinc-200 bg-white pl-1.5 pr-1 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/15 dark:border-zinc-700 dark:bg-zinc-800">
                                        <span class="relative size-6 shrink-0 overflow-hidden rounded-md shadow-inner ring-1 ring-black/10"
                                            :style="`background: ${c('{{ $key }}', '{{ $default }}')}`">
                                            <input type="color" title="Pick a color"
                                                :value="c('{{ $key }}', '{{ $default }}')"
                                                @input="$wire.set('settings.{{ $key }}', $event.target.value, false)"
                                                class="absolute inset-0 size-full cursor-pointer opacity-0">
                                        </span>
                                        <input id="{{ $key }}" type="text" maxlength="9" spellcheck="false"
                                            wire:model="settings.{{ $key }}" placeholder="{{ $default }}"
                                            class="h-full min-w-0 flex-1 border-0! bg-transparent p-0 font-mono text-xs uppercase text-zinc-700 shadow-none! outline-none placeholder:normal-case placeholder:text-zinc-400 focus:ring-0 dark:text-zinc-200">
                                        <button type="button" title="Reset to default"
                                            x-show="$wire.settings['{{ $key }}']" x-cloak
                                            @click="$wire.set('settings.{{ $key }}', '', false)"
                                            class="flex size-6 shrink-0 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-700">
                                            <flux:icon.arrow-uturn-left variant="micro" class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            {{-- Live preview — a miniature storefront painted with the values above. --}}
            <aside class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm xl:sticky xl:top-24 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-700">
                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Live preview</span>
                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:bg-emerald-500/10">Updates as you pick</span>
                </div>

                <div class="p-3">
                    <div class="overflow-hidden rounded-lg ring-1 ring-zinc-200 dark:ring-zinc-700" style="font-family: 'Trebuchet MS', 'Segoe UI', sans-serif">
                        {{-- Header --}}
                        <div class="flex items-center gap-2 px-2.5 py-2"
                            :style="`background: ${c('theme_ecommerce_header_bg_color', accent)}; color: ${c('theme_ecommerce_header_text_color', '#ffffff')}`">
                            <span class="size-3 rounded-full bg-current opacity-80"></span>
                            <span class="text-[10px] font-bold">Codeware</span>
                            <span class="ml-1 h-3.5 flex-1 rounded bg-white/90"></span>
                            <span class="size-2.5 rounded-sm border border-current opacity-80"></span>
                            <span class="size-2.5 rounded-full border border-current opacity-80"></span>
                        </div>
                        {{-- Menu bar --}}
                        <div class="flex gap-2.5 border-b border-black/5 px-2.5 py-1.5 text-[9px] font-semibold"
                            :style="`background: ${c('theme_ecommerce_nav_bg_color', '#ffffff')}; color: ${c('theme_ecommerce_nav_text_color', '#222222')}`">
                            <span>Categories</span><span>Brands</span><span :style="`color: ${accent}`">Home</span><span>Shop</span>
                        </div>
                        {{-- Page --}}
                        <div class="space-y-2 p-2.5" :style="`background: ${c('theme_ecommerce_page_bg_color', '#f2f4f8')}`">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold" :style="`color: ${c('theme_ecommerce_heading_color', '#171717')}`">Best sellers</span>
                                <span class="text-[9px] font-semibold" :style="`color: ${accent}`">View all →</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach (['Spearmint tea', 'Green tea'] as $i => $name)
                                    <div class="overflow-hidden rounded-md bg-white shadow-sm">
                                        <div class="relative h-12 bg-zinc-100">
                                            @if ($i === 0)
                                                <span class="absolute left-1 top-1 rounded px-1 text-[8px] font-bold text-white"
                                                    :style="`background: ${c('theme_ecommerce_sale_color', '#c01616')}`">-30%</span>
                                            @endif
                                        </div>
                                        <div class="space-y-1 p-1.5">
                                            <p class="truncate text-[9px] font-semibold" :style="`color: ${c('theme_ecommerce_heading_color', '#171717')}`">{{ $name }}</p>
                                            <p class="truncate text-[8px]" :style="`color: ${c('theme_ecommerce_text_color', '#262626')}`">100 gm · Organic</p>
                                            <p class="text-[10px] font-bold" :style="`color: ${c('theme_ecommerce_price_color', accent)}`">৳450</p>
                                            <span class="block rounded py-1 text-center text-[8px] font-semibold"
                                                :style="`background: ${c('theme_ecommerce_button_bg_color', accent)}; color: ${c('theme_ecommerce_button_text_color', '#ffffff')}`">Add to cart</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- Footer --}}
                        <div class="space-y-1 px-2.5 py-2" :style="`background: ${c('theme_ecommerce_footer_bg_color', accent)}; color: ${c('theme_ecommerce_footer_text_color', '#ffffff')}`">
                            <p class="text-[9px] font-bold">Quick Links</p>
                            <p class="text-[8px] opacity-75">About Us · Contact · FAQ</p>
                        </div>
                        <div class="px-2.5 py-1 text-[8px]" :style="`background: ${c('theme_ecommerce_footer_bottom_color', '#1d2327')}; color: ${c('theme_ecommerce_footer_text_color', '#ffffff')}`">
                            <span class="opacity-75">© Codeware. All rights reserved.</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
