@php
    // Per-theme preview mockups: each installed theme gets its own miniature
    // browser thumbnail (kept in sync with the actual templates).
    $frontendUrl = (string) (config('app.frontend_url') ?: url('/'));

    $previews = [
        'ecommerce' => [
            'bg'    => 'bg-[#f2f4f8]',
            'nav'   => 'bg-[#045b30]',
            'trim'  => 'text-[#045b30]',
            'hero'  => 'from-[#045b30] to-emerald-800',
            'cta'   => 'bg-white text-[#045b30]',
        ],
        'portfolio' => [
            'bg'    => 'bg-[#0a0e17]',
            'nav'   => 'bg-[#111827]',
            'trim'  => 'text-emerald-400',
            'hero'  => 'from-emerald-500 to-emerald-700',
            'cta'   => 'bg-emerald-500 text-white',
        ],
        'default' => [
            'bg'    => 'bg-slate-100',
            'nav'   => 'bg-slate-800',
            'trim'  => 'text-primary',
            'hero'  => 'from-[#1e7bc4] to-[#18599c]',
            'cta'   => 'bg-white text-[#1e7bc4]',
        ],
    ];
@endphp

@push('page-header-actions')
    <flux:button variant="outline" size="sm" icon="arrow-top-right-on-square" href="{{ $frontendUrl }}" target="_blank">
        View Public Site
    </flux:button>
@endpush

<div class="space-y-5">

    {{-- ── Live site status ─────────────────────────────────────────────── --}}
    <div
        class="flex flex-col gap-3 rounded-[5px] border border-zinc-200 bg-white px-5 py-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/40 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                <flux:icon.globe-alt class="size-5" />
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Public Site</p>
                <p class="truncate text-xs text-zinc-400">
                    Currently live on
                    <span class="font-mono font-medium text-zinc-500 dark:text-zinc-300">{{ $themes[$activeTheme] ?? $activeTheme }}</span>
                    — {{ $frontendUrl }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.settings') }}"
                class="text-xs font-medium text-zinc-400 transition-colors hover:text-zinc-600 dark:hover:text-zinc-200">Back to Settings</a>
        </div>
    </div>

    {{-- ── Site Design ── --}}
    <x-admin-section-card header-border="border-zinc-100" icon="swatch" title="Site Design"
        description="The design shown to visitors on the public site. Pick a theme card — changes apply once you save.">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($themes as $slug => $label)
                @php
                    $selected = ($settings['site_theme'] ?? null) === $slug;
                    $preview = $previews[$slug] ?? $previews['default'];
                @endphp

                <label
                    class="group relative flex cursor-pointer flex-col overflow-hidden rounded-xl border bg-white text-left transition-all duration-150
                        @if ($selected || $slug === $activeTheme)
                            border-primary ring-2 ring-primary/25
                        @else
                            border-zinc-200 hover:border-zinc-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800/40 dark:hover:border-zinc-500
                        @endif">

                    <input type="radio" name="site_theme" value="{{ $slug }}"
                        wire:model.live="settings.site_theme" class="sr-only">

                    {{-- Browser chrome --}}
                    <div class="flex items-center gap-1.5 border-b border-zinc-100 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/70">
                        <span class="size-2 rounded-full bg-rose-400"></span>
                        <span class="size-2 rounded-full bg-amber-400"></span>
                        <span class="size-2 rounded-full bg-emerald-400"></span>
                        <span class="ml-2 flex-1 truncate rounded bg-white px-2 py-0.5 text-[9px] font-medium text-zinc-400 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                            {{ $slug }} · codeware.test
                        </span>
                    </div>

                    {{-- Preview pane --}}
                    <div class="relative h-44 overflow-hidden {{ $preview['bg'] }} p-2.5 dark:bg-zinc-900">
                        <div class="flex h-full flex-col gap-1.5">

                            {{-- Nav bar --}}
                            <div class="flex items-center justify-between rounded-md {{ $preview['nav'] }} px-2.5 py-1.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="size-2.5 rounded-sm bg-white/90"></span>
                                    <span class="h-1.5 w-8 rounded bg-white/40"></span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-1.5 w-6 rounded bg-white/30"></span>
                                    <span class="h-1.5 w-6 rounded bg-white/30"></span>
                                    <span class="h-1.5 w-6 rounded bg-white/30"></span>
                                </div>
                            </div>

                            @if ($slug === 'ecommerce')
                                {{-- Hero + "Shop now" --}}
                                <div class="relative flex flex-1 items-end rounded-md bg-gradient-to-br {{ $preview['hero'] }} px-2.5 pb-2 pt-3">
                                    <span class="absolute left-2.5 top-2 h-1.5 w-16 rounded bg-white/50"></span>
                                    <span class="absolute left-2.5 top-3.5 h-1.5 w-24 rounded bg-white/70"></span>
                                    <span class="rounded bg-white px-2 py-0.5 text-[8px] font-bold uppercase {{ $preview['trim'] }}">Shop now</span>
                                </div>
                                {{-- Product tiles --}}
                                <div class="grid grid-cols-4 gap-1.5">
                                    @foreach ([1, 2, 3, 4] as $tile)
                                        <div class="flex flex-col items-center gap-1 rounded-md bg-white p-1.5 ring-1 ring-zinc-100">
                                            <div class="w-full rounded-sm bg-zinc-200" style="height: 14px"></div>
                                            <div class="h-1 w-5 rounded bg-zinc-300"></div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($slug === 'portfolio')
                                {{-- Dark portfolio hero --}}
                                <div class="flex flex-1 flex-col items-center justify-center rounded-md border border-white/5 px-3 text-center">
                                    <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 font-mono text-[8px] font-semibold text-emerald-300 ring-1 ring-emerald-500/30">
                                        Full Stack Developer
                                    </span>
                                    <div class="mt-2 space-y-1">
                                        <div class="mx-auto h-1.5 w-24 rounded bg-zinc-600"></div>
                                        <div class="mx-auto h-1.5 w-20 rounded bg-gradient-to-r {{ $preview['hero'] }}"></div>
                                    </div>
                                    <div class="mt-3 flex gap-1.5">
                                        <span class="rounded-full {{ $preview['cta'] }} px-2.5 py-0.5 text-[8px] font-semibold">View Projects</span>
                                        <span class="rounded-full border border-white/20 px-2.5 py-0.5 text-[8px] font-semibold text-zinc-300">Contact Me</span>
                                    </div>
                                </div>
                                <div class="flex justify-center gap-1.5">
                                    @foreach ([1, 2, 3, 4, 5] as $dot)
                                        <span class="size-1 rounded-full bg-emerald-400/60"></span>
                                    @endforeach
                                </div>
                            @else
                                {{-- Auth-style landing card --}}
                                <div class="flex flex-1 flex-col items-center justify-center gap-2">
                                    <div class="w-3/4 rounded-md bg-white px-3 py-2.5 text-center shadow-sm">
                                        <div class="mx-auto flex items-center justify-center gap-1.5">
                                            <span class="size-3 rounded-sm bg-[#7cc242]"></span>
                                            <span class="h-1.5 w-12 rounded bg-slate-300"></span>
                                        </div>
                                        <div class="mt-2 space-y-1">
                                            <div class="h-1.5 w-full rounded bg-slate-200"></div>
                                            <div class="h-1.5 w-full rounded bg-slate-200"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="rounded bg-[#1e7bc4] px-3 py-1 text-[8px] font-bold text-white">Admin Login</span>
                                        <span class="rounded bg-[#7cc242] px-3 py-1 text-[8px] font-bold text-white">Vendor Login</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center gap-3 border-t border-zinc-100 px-3.5 py-2.5 dark:border-zinc-700">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $label }}</p>
                                @if ($slug === $activeTheme)
                                    <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        Live
                                    </span>
                                @elseif ($selected)
                                    <span class="shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-primary">
                                        Selected
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate font-mono text-[10px] text-zinc-400">
                                {{ $themeCards[$slug]['templates'] }} templates
                                @if ($themeCards[$slug]['shop'])
                                    · shop pages
                                @endif
                            </p>
                        </div>
                        <span x-show="$wire.settings.site_theme === '{{ $slug }}'" x-cloak
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow">
                            <flux:icon.check class="size-3.5" />
                        </span>
                    </div>
                </label>
            @endforeach
        </div>

        <p class="flex items-center gap-1.5 text-xs text-zinc-400">
            <flux:icon.sparkles class="size-3.5 text-amber-400" />
            Adding a theme is as simple as dropping a new folder into
            <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">resources/views/frontend/themes/</code>
            — it shows up here automatically.
        </p>
    </x-admin-section-card>

    {{-- ── Homepage ── --}}
    <x-admin-section-card header-border="border-zinc-100" icon="home" title="Homepage"
        description="Copy and imagery the homepage of your theme renders on the public site.">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            {{-- Left: text + behavior --}}
            <div class="space-y-5">
                <flux:field>
                    <flux:label>Site Tagline<x-field-hint text="A short line under the site name on the homepage." /></flux:label>
                    <flux:textarea wire:model="settings.site_tagline" class="h-24" placeholder="e.g. Your one-stop shop for everything" />
                </flux:field>

                <div class="rounded-lg border border-zinc-100 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                <flux:icon.chat-bubble-oval-left class="size-4" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Live Chat Widget</p>
                                <p class="text-xs text-zinc-400">Support bubble on every public page.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model="settings.chat_widget_enabled" class="peer sr-only">
                            <div
                                class="h-5 w-9 rounded-full bg-zinc-300 transition-colors peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/40 dark:bg-zinc-600"></div>
                            <div
                                class="absolute left-0.5 top-0.5 size-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Right: imagery --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                @php
                    $imageSlots = [
                        ['key' => 'settings.home_hero_image', 'label' => 'Hero Image', 'hint' => 'Large banner at the top', 'tint' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400'],
                        ['key' => 'settings.home_promo_banner_1', 'label' => 'Promo Banner 1', 'hint' => 'Strip below the hero', 'tint' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400'],
                        ['key' => 'settings.home_promo_banner_2', 'label' => 'Promo Banner 2', 'hint' => 'Second promotional strip', 'tint' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400'],
                    ];
                @endphp

                @foreach ($imageSlots as $slot)
                    <div class="rounded-lg border border-zinc-100 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                        <div class="mb-3 flex items-center gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $slot['tint'] }}">
                                <flux:icon.photo class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $slot['label'] }}</p>
                                <p class="text-xs leading-tight text-zinc-400">{{ $slot['hint'] }}</p>
                            </div>
                        </div>
                        <x-media-picker :model="$slot['key']" label="" only-images mimes="jpg,jpeg,png,gif,webp"
                            :max-size-mb="4" placeholder="Choose from the library" />
                    </div>
                @endforeach
            </div>
        </div>
    </x-admin-section-card>

    {{-- ── Save bar ── --}}
    <div
        class="sticky bottom-0 z-10 flex flex-col gap-3 rounded-[5px] border border-zinc-200 bg-white/95 px-5 py-3.5 shadow-[0_-4px_16px_-8px_rgba(0,0,0,0.15)] backdrop-blur dark:border-zinc-700 dark:bg-zinc-800/90 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-zinc-400">
            Your changes apply to the public site once saved.
        </p>
        <div class="flex items-center gap-2.5">
            <flux:button variant="outline" wire:click="resetSettings" size="sm">
                Discard
            </flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" size="sm">
                <span wire:loading.remove>Save Theme Settings</span>
                <span wire:loading>Saving…</span>
            </flux:button>
        </div>
    </div>

</div>