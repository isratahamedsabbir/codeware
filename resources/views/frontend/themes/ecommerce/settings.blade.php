{{--
    Ecommerce theme settings — bound to the Theme Settings screen (Admin →
    Theme Settings) via wire:model="settings.*". Values persist to the settings
    table and are read by ecommerce/home.blade.php and partials/head.blade.php.

    Banners tab: the hero slider (each slide's image, title, description and
    link) and the two promo tiles with their links. Colors tab: one color per
    storefront area, with a live preview.
--}}
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

    @php
        $promoTiles = [
            ['home_promo_banner_1', 'theme_ecommerce_promo_1_link', 'New arrivals', 'Top tile', '/shop?sort=newest'],
            ['home_promo_banner_2', 'theme_ecommerce_promo_2_link', 'Best deals', 'Bottom tile', '/shop'],
        ];
    @endphp

    {{-- Banners — laid out exactly like the storefront's homepage header: the
         hero slider on the left, the two promo tiles stacked beside it. Every
         slide's picker and fields stay mounted (x-show) so each keeps its
         Livewire binding while you switch slides. `isolate` keeps the
         z-indexed cards on the images from rising above the sticky page
         header and save bar. --}}
    <section role="tabpanel" x-show="tab === 'banners'" x-data="{ activeSlide: 0 }"
        class="isolate overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.rectangle-group variant="mini" class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Homepage banners</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Arranged as they appear on the storefront. Click any area to choose an image.</p>
                </div>
            </div>
        </header>

        {{-- ── Canvas: hero (2/3) + promo tiles (1/3) ── --}}
        <div class="bg-zinc-50/70 p-5 dark:bg-zinc-800/40">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                {{-- Hero slider --}}
                <div class="min-w-0 space-y-3 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Hero slider
                            <span class="rounded-full bg-zinc-200/70 px-2 py-0.5 text-[10px] font-semibold normal-case tracking-normal text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                {{ count($heroSlides) }} / {{ \App\Livewire\Admin\ThemeSettings\Index::MAX_HERO_SLIDES }}
                            </span>
                        </p>
                        <span class="text-[11px] text-zinc-400">1920 × 600</span>
                    </div>

                    {{-- Each slide's text and link sit on the image itself, where
                         the storefront shows them. The fields are a sibling of
                         the picker (not inside its button), so typing never
                         opens the media library. --}}
                    @foreach ($heroSlides as $i => $slide)
                        <div wire:key="hero-slide-{{ $i }}" x-show="activeSlide === {{ $i }}" @if ($i > 0) x-cloak @endif class="relative">
                            <x-media-picker model="heroSlides.{{ $i }}.image" label="Slide {{ $i + 1 }}" size-hint="1920 × 600" preview dropzone drop-height="h-[29rem]"
                                only-images mimes="jpg,jpeg,png,gif,webp,avif" :max-size-mb="4" placeholder="Choose from the library" />
                            @if (filled($slide['image']))
                                <span class="pointer-events-none absolute left-3 top-3 z-10 rounded-md bg-zinc-900/75 px-2 py-1 text-[11px] font-semibold text-white backdrop-blur">
                                    Slide {{ $i + 1 }}
                                </span>
                            @endif

                            <div class="absolute inset-x-4 bottom-4 z-20 space-y-2">
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_14rem]">
                                    <flux:input size="sm" wire:model="heroSlides.{{ $i }}.title" placeholder="Title — e.g. Fresh organic tea" maxlength="120" aria-label="Slide {{ $i + 1 }} title" />
                                    <flux:input size="sm" wire:model="heroSlides.{{ $i }}.link" icon="link" placeholder="/shop" aria-label="Slide {{ $i + 1 }} link"
                                        title="A page on this site (e.g. /shop?category=tea) or a full https:// address. Blank opens the Shop page." />
                                </div>
                                <flux:textarea wire:model="heroSlides.{{ $i }}.description" rows="2" class="resize-none text-sm"
                                    placeholder="Description — e.g. Hand-picked leaves, delivered fresh to your door." maxlength="300" aria-label="Slide {{ $i + 1 }} description" />
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center gap-2 overflow-x-auto pb-1">
                        @foreach ($heroSlides as $i => $slide)
                            <div wire:key="hero-tab-{{ $i }}" class="group/tab relative shrink-0 pt-1.5 pr-1.5">
                                <button type="button" x-on:click="activeSlide = {{ $i }}" aria-label="Slide {{ $i + 1 }}"
                                    x-bind:class="activeSlide === {{ $i }}
                                        ? 'border-primary ring-2 ring-primary/20'
                                        : 'border-zinc-200 opacity-75 hover:opacity-100 hover:border-zinc-300 dark:border-zinc-700'"
                                    class="relative flex h-12 w-20 items-center justify-center overflow-hidden rounded-lg! border-2 bg-zinc-100 text-xs font-semibold text-zinc-500 transition dark:bg-zinc-800">
                                    @if (filled($slide['image']))
                                        <img src="{{ $slide['image'] }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <flux:icon.photo variant="mini" class="size-4 text-zinc-400" />
                                    @endif
                                    <span class="absolute bottom-0.5 left-0.5 rounded bg-zinc-900/70 px-1 text-[10px] font-bold leading-4 text-white">{{ $i + 1 }}</span>
                                </button>
                                @if (count($heroSlides) > 1)
                                    <button type="button" title="Remove slide {{ $i + 1 }}"
                                        wire:click="removeHeroSlide({{ $i }})"
                                        x-on:click="activeSlide = Math.max(0, Math.min(activeSlide, $wire.heroSlides.length - 2))"
                                        class="absolute right-0 top-0 hidden size-5 items-center justify-center rounded-full! bg-white text-red-500 shadow ring-1 ring-zinc-200 group-hover/tab:flex dark:bg-zinc-800 dark:ring-zinc-700">
                                        <flux:icon.x-mark variant="micro" class="size-3" />
                                    </button>
                                @endif
                            </div>
                        @endforeach

                        @if (count($heroSlides) < \App\Livewire\Admin\ThemeSettings\Index::MAX_HERO_SLIDES)
                            {{-- Switches to the new slide only once the server has added it —
                                 switching first leaves no slide to show until the response
                                 lands, and the hero collapses then grows back. The click
                                 expressions here and on the remove buttons read the count
                                 from $wire rather than baking it in: an x-on:click whose text
                                 changes between renders gets rebound by the morph, and the
                                 button stops working after the first click. --}}
                            <div wire:key="hero-tab-add" class="shrink-0 pt-1.5 pr-1.5">
                                <button type="button" title="Add slide" aria-label="Add slide"
                                    x-on:click="$wire.addHeroSlide().then(() => activeSlide = $wire.heroSlides.length - 1)"
                                    class="flex h-12 w-20 flex-col items-center justify-center gap-0.5 rounded-lg! border-2 border-dashed border-zinc-300 text-zinc-400 transition hover:border-primary hover:bg-primary/5 hover:text-primary dark:border-zinc-600">
                                    <flux:icon.plus variant="mini" class="size-4" />
                                    <span class="text-[10px] font-semibold leading-none">Add</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Promo tiles — stacked beside the hero on desktop, side by
                     side under it on smaller screens, as on the storefront. --}}
                <div class="min-w-0 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Promo tiles</p>
                        <span class="text-[11px] text-zinc-400">1200 × 400</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-1">
                        @foreach ($promoTiles as [$imageKey, $linkKey, $promoLabel, $promoPosition, $example])
                            <div class="relative min-w-0">
                                <x-media-picker model="settings.{{ $imageKey }}" label="{{ $promoLabel }}" size-hint="1200 × 400" preview dropzone drop-height="h-[14rem]"
                                    only-images mimes="jpg,jpeg,png,gif,webp,avif" :max-size-mb="4" placeholder="Choose from the library" />
                                @if (filled($settings[$imageKey] ?? null))
                                    <span class="pointer-events-none absolute left-3 top-3 z-10 rounded-md bg-zinc-900/75 px-2 py-1 text-[11px] font-semibold text-white backdrop-blur">
                                        {{ $promoLabel }} · {{ $promoPosition }}
                                    </span>
                                @endif

                                {{-- The tile's link, on the tile (see the hero fields above). --}}
                                <div class="absolute inset-x-3 bottom-3 z-20">
                                    <flux:input size="sm" wire:model="settings.{{ $linkKey }}" icon="link" placeholder="{{ $example }}" aria-label="{{ $promoLabel }} link"
                                        title="A page on this site or a full https:// address. Blank opens the Shop page." />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

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
