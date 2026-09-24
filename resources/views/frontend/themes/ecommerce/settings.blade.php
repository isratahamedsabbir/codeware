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
    $bannerChip = 'pointer-events-none absolute left-3 top-3 z-10 inline-flex items-center gap-1.5 rounded-md bg-zinc-900/75 px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm backdrop-blur';
@endphp

<div class="space-y-6">
    <div>
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">Homepage banners</p>
        <div class="grid grid-cols-1 gap-4 lg:h-[26rem] lg:grid-cols-3 lg:grid-rows-2">
            {{-- Hero slider — one upload box per slide; the tabs along the bottom
                 switch between slides, add one, or remove one. Every picker stays
                 mounted (only the active one is shown) so each keeps its binding. --}}
            <div class="relative lg:col-span-2 lg:row-span-2" x-data="{ activeSlide: 0 }">
                @foreach ($heroSlides as $i => $slide)
                    <div wire:key="hero-slide-{{ $i }}" x-show="activeSlide === {{ $i }}" @if ($i > 0) x-cloak @endif>
                        <x-media-picker model="heroSlides.{{ $i }}" label="Hero slide {{ $i + 1 }}" size-hint="1920 × 600" preview dropzone drop-height="h-64 lg:h-[26rem]"
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
                                @if (filled($slide))
                                    <img src="{{ $slide }}" alt="" class="h-full w-full object-cover">
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
            </div>

            {{-- Promo 2 — "Best deals" tile --}}
            <div class="relative">
                <x-media-picker model="settings.home_promo_banner_2" label="Best deals" size-hint="1200 × 400" preview dropzone drop-height="h-44 lg:h-[12.5rem]"
                    only-images mimes="jpg,jpeg,png,gif,webp" :max-size-mb="4" placeholder="Choose from the library" />
                <span class="{{ $bannerChip }}">
                    <flux:icon.photo variant="micro" class="size-3.5" />
                    Promo · Best deals <span class="font-normal text-white/60">· 1200 × 400</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Colors — one per storefront area. Blank means "use the default"
         (shown as the swatch until you pick one); partials/head.blade.php
         turns each into the matching --color-* CSS variable. --}}
    @php
        $colorGroups = [
            'Header' => [
                ['theme_ecommerce_header_bg_color', 'Background', '#045b30', 'Top bar with the logo, search and account.'],
                ['theme_ecommerce_header_text_color', 'Text & icons', '#ffffff', 'Site name, phone number, icons.'],
            ],
            'Menu bar' => [
                ['theme_ecommerce_nav_bg_color', 'Background', '#ffffff', 'The Categories / Brands / pages row.'],
                ['theme_ecommerce_nav_text_color', 'Links', '#222222', 'Menu link text.'],
            ],
            'Footer' => [
                ['theme_ecommerce_footer_bg_color', 'Background', '#045b30', 'Main footer area.'],
                ['theme_ecommerce_footer_text_color', 'Text', '#ffffff', 'Headings, links and contact details.'],
                ['theme_ecommerce_footer_bottom_color', 'Copyright bar', '#1d2327', 'The thin strip at the very bottom.'],
            ],
            'Buttons' => [
                ['theme_ecommerce_button_bg_color', 'Background', '#045b30', 'Add to cart, Checkout, Place order, Shop now…'],
                ['theme_ecommerce_button_text_color', 'Text', '#ffffff', 'Label on those buttons.'],
            ],
            'Text' => [
                ['theme_ecommerce_heading_color', 'Headings', '#171717', 'Titles, product names and totals.'],
                ['theme_ecommerce_text_color', 'Body text', '#262626', 'Regular copy and labels.'],
                ['theme_ecommerce_price_color', 'Prices', '#045b30', 'Product prices on cards and the product page.'],
            ],
            'Accents' => [
                ['theme_ecommerce_accent_color', 'Links & highlights', '#045b30', 'Links, selected filters, badges, active states.'],
                ['theme_ecommerce_sale_color', 'Sale badge', '#c01616', 'The "-30%" discount badge.'],
                ['theme_ecommerce_page_bg_color', 'Page background', '#f2f4f8', 'Background behind all storefront pages.'],
            ],
        ];
    @endphp

    <div>
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">Colors</p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($colorGroups as $group => $fields)
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <p class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $group }}</p>
                    <div class="space-y-3">
                        @foreach ($fields as [$key, $label, $default, $hint])
                            <div x-data="{ fallback: '{{ $default }}' }">
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="{{ $key }}" class="flex items-center text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                        {{ $label }}<x-field-hint :text="$hint" />
                                    </label>
                                    <button type="button" x-show="$wire.settings['{{ $key }}']" x-cloak
                                        @click="$wire.set('settings.{{ $key }}', '', false)"
                                        class="text-[11px] font-medium text-zinc-400 hover:text-red-500">Reset</button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="color"
                                        :value="$wire.settings['{{ $key }}'] || fallback"
                                        @input="$wire.set('settings.{{ $key }}', $event.target.value, false)"
                                        class="h-9 w-11 shrink-0 cursor-pointer rounded-lg border border-zinc-300 bg-white p-1 dark:border-zinc-600 dark:bg-zinc-800">
                                    <flux:input id="{{ $key }}" wire:model="settings.{{ $key }}" placeholder="{{ $default }}" size="sm" class="font-mono" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
