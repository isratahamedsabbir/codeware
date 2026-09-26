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
            'bg'    => 'bg-[#06080f]',
            'nav'   => 'bg-[#0b0f1a]',
            'trim'  => 'text-indigo-400',
            'hero'  => 'from-indigo-500 to-cyan-400',
            'cta'   => 'bg-indigo-500 text-white',
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
    {{-- Plain onclick (same cross-DOM approach as the Media Library's header
         buttons): this is rendered by the layout's header via @push/@stack, so
         it has no wire:id ancestor to call $wire on. The root <div> below
         forwards the event (x-on:open-theme-install.window) to openInstallModal(). --}}
    <flux:button variant="outline" size="sm" icon="arrow-up-tray"
        onclick="window.dispatchEvent(new CustomEvent('open-theme-install'))">
        Install Theme
    </flux:button>

    <flux:button variant="outline" size="sm" icon="arrow-top-right-on-square" href="{{ $frontendUrl }}" target="_blank">
        View Public Site
    </flux:button>
@endpush

<div class="space-y-5" x-data="{ showThemeGuide: false }" x-on:open-theme-install.window="$wire.openInstallModal()">

    {{-- ── Site Design ── --}}
    <x-admin-section-card header-border="border-zinc-100" icon="swatch" title="Site Design"
        description="The design shown to visitors on the public site. Pick a theme card — changes apply once you save."
        collapsible :collapsed="true">

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
                                {{-- Split portfolio hero: copy on the left, portrait on the right,
                                     with the stat strip peeking below it. --}}
                                <div class="flex flex-1 items-center gap-2 rounded-md border border-white/5 px-2.5 py-3">
                                    <div class="flex flex-1 flex-col items-start gap-1.5 text-left">
                                        <span class="rounded-full bg-indigo-500/15 px-2 py-0.5 font-mono text-[8px] font-semibold text-indigo-300 ring-1 ring-indigo-500/30">
                                            Available for work
                                        </span>
                                        <div class="h-1.5 w-16 rounded bg-zinc-600"></div>
                                        <div class="h-1.5 w-20 rounded bg-gradient-to-r {{ $preview['hero'] }}"></div>
                                        <div class="mt-0.5 flex gap-1">
                                            <span class="rounded-full {{ $preview['cta'] }} px-1.5 py-0.5 text-[7px] font-semibold">Projects</span>
                                            <span class="rounded-full border border-white/20 px-1.5 py-0.5 text-[7px] font-semibold text-zinc-300">CV</span>
                                        </div>
                                    </div>
                                    <span class="h-14 w-11 shrink-0 rounded-md border border-white/10 bg-gradient-to-br from-indigo-500/30 to-cyan-400/20"></span>
                                </div>
                                <div class="flex justify-between gap-1 rounded-md border border-white/5 px-2 py-1.5">
                                    @foreach (range(1, 4) as $stat)
                                        <span class="h-1 flex-1 rounded bg-zinc-700"></span>
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

        {{-- Selected theme's own settings (read from the theme folder's theme.json) --}}
        @php
            $selectedSlug = $settings['site_theme'] ?? $activeTheme;
            $selectedCard = $themeCards[$selectedSlug] ?? null;
        @endphp

        @if ($selectedCard)
            <div class="mt-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800/40">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                        {{ $selectedCard['manifest']['name'] }}
                    </h4>

                    @if (filled($selectedCard['manifest']['version']))
                        <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 font-mono text-[11px] font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300">
                            v{{ $selectedCard['manifest']['version'] }}
                        </span>
                    @endif

                    @if ($selectedSlug === $activeTheme)
                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            Live
                        </span>
                    @endif

                    @if (filled($selectedCard['manifest']['author']))
                        <span class="ml-auto text-xs text-zinc-400">
                            by {{ $selectedCard['manifest']['author'] }}
                        </span>
                    @endif
                </div>

                @if (filled($selectedCard['manifest']['description']))
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        {{ $selectedCard['manifest']['description'] }}
                    </p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="flex items-center gap-1.5">
                        <flux:icon.document-text class="size-3.5 text-zinc-400" />
                        {{ $selectedCard['templates'] }} templates
                    </span>
                    @if ($selectedCard['shop'])
                        <span class="flex items-center gap-1.5">
                            <flux:icon.shopping-bag class="size-3.5 text-zinc-400" />
                            shop pages
                        </span>
                    @endif
                    @foreach ($selectedCard['manifest']['tags'] as $tag)
                        <span class="rounded-md border border-zinc-200 bg-zinc-50 px-2 py-0.5 font-mono text-[11px] text-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="flex items-center gap-1.5 text-xs text-zinc-400">
            <flux:icon.sparkles class="size-3.5 text-amber-400" />
            Adding a theme is as simple as dropping a new folder into
            <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">resources/views/frontend/themes/</code>
            — it shows up here automatically.
        </p>
    </x-admin-section-card>

    {{-- ── Theme's own settings ──────────────────────────────────────────────
         If the selected theme ships a settings.blade.php at its root, render it
         inline (its fields bind to settings.theme_{slug}_* keys, which this
         component hydrates on mount and persists on save). --}}
    @if ($selectedHasSettings)
        <x-admin-section-card header-border="border-zinc-100" icon="adjustments-horizontal" title="Theme Settings"
            description="Settings the selected theme ({{ $selectedSlug }}) defines itself — saved under the theme_{{ $selectedSlug }}_ prefix.">
            <x-slot:titleActions>
                <button type="button" @click="showThemeGuide = true" title="How theme settings work"
                    class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-4" />
                </button>
            </x-slot:titleActions>
            @include('frontend.themes.'.$selectedSlug.'.settings', [
                'themeSlug' => $selectedSlug,
                'settings' => $settings,
            ])
        </x-admin-section-card>
    @endif

    {{-- ── Live Chat Widget ───────────────────────────────────────────────── --}}
    <x-admin-section-card header-border="border-zinc-100" icon="chat-bubble-left-right" title="Live Chat Widget"
        description="The support chat bubble on the public site. Visitors verify with an email code before chatting.">

        <x-slot:actions>
            <flux:switch wire:model="settings.chat_widget_enabled" aria-label="Enable Live Chat" title="Enable Live Chat" />
        </x-slot:actions>

        <div x-data="{
                fallback: '#1e7bc4',
                presets: ['#1e7bc4', '#042b49', '#0f766e', '#16a34a', '#7c3aed', '#db2777', '#ea580c', '#18181b'],
                get color() { return $wire.settings.chat_widget_color || '' },
                get valid() { return /^#[0-9a-fA-F]{6}$/.test(this.color) },
                get preview() { return this.valid ? this.color : this.fallback },
                pick(hex) { $wire.settings.chat_widget_color = hex },
            }"
            class="grid grid-cols-1 gap-6 transition-opacity lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]"
            :class="! $wire.settings.chat_widget_enabled && 'opacity-60'">

            {{-- Controls --}}
            <div class="space-y-5">
                <flux:field>
                    <div class="flex items-center justify-between gap-2">
                        <flux:label>Widget Color</flux:label>
                        <span x-show="! color"
                            class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300">
                            <flux:icon.link class="size-3" /> Follows site primary
                        </span>
                        <span x-show="color" x-cloak
                            class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">
                            <flux:icon.swatch class="size-3" /> Custom
                        </span>
                    </div>
                    <flux:description>Used for the chat bubble, header, buttons and visitor messages. Leave blank to follow the site's primary color.</flux:description>

                    <div class="flex items-center gap-2">
                        <label class="relative size-10 shrink-0 cursor-pointer overflow-hidden rounded-lg shadow-xs ring-1 ring-black/10 ring-inset transition hover:scale-105"
                            :style="'background-color: ' + preview" title="Open color picker">
                            <input type="color" class="absolute inset-0 size-full cursor-pointer opacity-0"
                                :value="preview"
                                x-on:input="pick($event.target.value)"
                                aria-label="Pick widget color" />
                            <flux:icon.eye-dropper class="pointer-events-none absolute right-0.5 bottom-0.5 size-3 text-white/80 drop-shadow" />
                        </label>
                        <flux:input wire:model.live.debounce.300ms="settings.chat_widget_color" placeholder="Site primary color" class="flex-1 font-mono uppercase" />
                        <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" x-show="color" x-cloak
                            x-on:click="pick('')" title="Reset to site primary color" />
                    </div>
                    <flux:error name="settings.chat_widget_color" />
                </flux:field>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">Quick picks</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="hex in presets" :key="hex">
                            <button type="button" x-on:click="pick(hex)" :title="hex"
                                class="relative flex size-8 cursor-pointer items-center justify-center rounded-full ring-1 ring-black/10 ring-inset transition hover:scale-110 focus:outline-none"
                                :class="color.toLowerCase() === hex && 'ring-2 ring-offset-2 ring-zinc-400 dark:ring-offset-zinc-900'"
                                :style="'background-color: ' + hex">
                                <flux:icon.check x-show="color.toLowerCase() === hex" class="size-4 text-white" />
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex items-start gap-2.5 rounded-lg border border-zinc-100 bg-zinc-50 p-3 text-xs leading-relaxed text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400">
                    <flux:icon.shield-check class="mt-px size-4 shrink-0 text-emerald-500" />
                    <span>Visitors confirm their email with a one-time code before a conversation starts — this keeps spam out of your inbox.</span>
                </div>
            </div>

            {{-- Live preview --}}
            <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50"
                :style="'--preview: ' + preview">
                {{-- fake browser bar --}}
                <div class="flex items-center gap-1.5 border-b border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                    <span class="size-2 rounded-full bg-red-400"></span>
                    <span class="size-2 rounded-full bg-amber-400"></span>
                    <span class="size-2 rounded-full bg-emerald-400"></span>
                    <span class="ml-3 h-4 flex-1 rounded bg-zinc-100 dark:bg-zinc-700"></span>
                    <span class="ml-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-400">Preview</span>
                </div>

                <div class="relative h-72 bg-[radial-gradient(circle,rgb(0_0_0/0.06)_1px,transparent_1px)] bg-size-[14px_14px] p-4 dark:bg-[radial-gradient(circle,rgb(255_255_255/0.06)_1px,transparent_1px)]">
                    {{-- page skeleton --}}
                    <div class="space-y-2 opacity-70">
                        <div class="h-3 w-1/3 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-2 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-2 w-1/2 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                    </div>

                    {{-- chat window --}}
                    <div class="absolute right-4 bottom-20 w-60 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center gap-2.5 px-3.5 py-3 text-white" style="background-color: var(--preview)">
                            <span class="relative flex size-8 items-center justify-center rounded-full bg-white/20">
                                <flux:icon.chat-bubble-left-right class="size-4" />
                                <span class="absolute -right-0.5 -bottom-0.5 size-2.5 rounded-full bg-emerald-400 ring-2 ring-white/80"></span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold leading-tight">Chat with us</p>
                                <p class="truncate text-[10px] text-white/80">We're online — ask us anything.</p>
                            </div>
                            <flux:icon.x-mark class="size-3.5 text-white/70" />
                        </div>
                        <div class="space-y-2 p-3">
                            <div class="w-fit max-w-[80%] rounded-2xl rounded-bl-sm bg-zinc-100 px-3 py-1.5 text-[11px] text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">Hi! How can we help?</div>
                            <div class="ml-auto w-fit max-w-[80%] rounded-2xl rounded-br-sm px-3 py-1.5 text-[11px] text-white" style="background-color: var(--preview)">I have a question</div>
                        </div>
                        <div class="flex items-center gap-2 border-t border-zinc-100 px-3 py-2 dark:border-zinc-700">
                            <span class="h-6 flex-1 rounded-full bg-zinc-100 px-2.5 text-[10px] leading-6 text-zinc-400 dark:bg-zinc-700">Type a message…</span>
                            <span class="flex size-6 items-center justify-center rounded-full text-white" style="background-color: var(--preview)">
                                <flux:icon.paper-airplane class="size-3" />
                            </span>
                        </div>
                    </div>

                    {{-- launcher bubble --}}
                    <div class="absolute right-4 bottom-4 flex size-12 items-center justify-center rounded-full text-white shadow-lg ring-4 ring-white dark:ring-zinc-900"
                        style="background-color: var(--preview)">
                        <flux:icon.chat-bubble-oval-left class="size-5" />
                    </div>
                </div>
            </div>
        </div>
    </x-admin-section-card>

    {{-- ── Popup (announcement) ────────────────────────────────────────────── --}}
    <x-admin-section-card header-border="border-zinc-100" icon="megaphone" title="Popup"
        description="A one-time announcement popup for visitors: it appears on their first visit and, once closed, never bothers them again."
        collapsible :collapsed="true">

        <x-slot:actions>
            <flux:switch wire:model="settings.popup_enabled" aria-label="Show Announcement Popup" title="Show Announcement Popup" />
        </x-slot:actions>

        <div class="space-y-7">

            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">Content</p>
                <div class="grid grid-cols-1 gap-5">
                    <x-media-picker model="settings.popup_image" label="Background Image" size-hint="Recommended 800 × 600" preview dropzone drop-height="h-44"
                        only-images mimes="jpg,jpeg,png,gif,webp,avif" :max-size-mb="4" placeholder="Choose from the library" />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <flux:label>Title</flux:label>
                            <flux:input wire:model="settings.popup_title" placeholder="e.g. Welcome to our store" />
                        </div>
                        <div class="sm:col-span-2">
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="settings.popup_description" class="h-24 resize-none"
                                placeholder="e.g. Get 10% off your first order with code WELCOME10" />
                        </div>
                        <div>
                            <flux:label>Button Label</flux:label>
                            <flux:input wire:model="settings.popup_button_label" placeholder="e.g. Shop Now" />
                        </div>
                        <div>
                            <flux:label>Button Link</flux:label>
                            <flux:input wire:model="settings.popup_button_url" placeholder="e.g. /shop or https://..." />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-admin-section-card>

    {{-- ── Install Theme modal ─────────────────────────────────────────────── --}}
    @if ($showInstallModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
                @click.away="$wire.closeInstallModal()">

                <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-700">
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Install Theme</h3>
                    <button wire:click="closeInstallModal"
                        class="rounded p-1 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid gap-5 p-6">
                    <p class="text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Upload your theme as a
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">.zip</code>
                        of a single folder — e.g.
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">my-theme/</code>
                        containing your
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">home.blade.php</code>,
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">page.blade.php</code>
                        etc. The folder name becomes the theme's name, and it shows up in the picker above immediately after installing.
                    </p>

                    <a href="{{ asset('docs/theme-builder-guide.pdf') }}" target="_blank"
                        class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 p-4 transition-colors hover:border-emerald-300 hover:bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:hover:border-emerald-500/50">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                            <flux:icon.document-text class="size-5" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">Theme Builder Guide</span>
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">How to structure, package and install a theme — open the PDF in a new tab</span>
                        </span>
                        <flux:icon.arrow-top-right-on-square class="ml-auto size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                    </a>

                    <div class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-zinc-300 bg-zinc-50/60 p-5 text-center dark:border-zinc-600 dark:bg-zinc-800/30">
                        <input type="file" wire:model="themeZip" accept=".zip" class="hidden" id="theme-zip-input">
                        @if ($themeZip)
                            <p class="max-w-full truncate text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $themeZip->getClientOriginalName() }}</p>
                        @else
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Choose a .zip file to upload</p>
                        @endif
                        <flux:button variant="outline" size="sm"
                            onclick="document.getElementById('theme-zip-input').click()">
                            Choose File
                        </flux:button>
                    </div>

                    @error('themeZip')
                        <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <flux:button variant="ghost" size="sm" wire:click="closeInstallModal">Cancel</flux:button>
                    <flux:button variant="primary" size="sm" wire:click="installTheme" wire:loading.attr="disabled">
                        <span wire:loading.remove>Install Theme</span>
                        <span wire:loading>Installing…</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Theme Settings Guide modal ─────────────────────────────────────── --}}
    <div x-show="showThemeGuide" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @keydown.escape.window="showThemeGuide = false">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
            @click.away="showThemeGuide = false">

            <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-700">
                <h3 class="flex items-center gap-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    <flux:icon.information-circle class="size-4 text-primary" />
                    Theme Settings Guide
                </h3>
                <button type="button" @click="showThemeGuide = false"
                    class="rounded p-1 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-4 p-6 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                <p>
                    Themes can define their own settings. Drop a
                    <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[11px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">settings.blade.php</code>
                    file inside the theme folder and it appears in this panel automatically.
                </p>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">1. Fields bind to settings</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Use Flux fields. Each control's key starts with the theme slug so settings stay namespaced per theme:
                    </p>
                    <pre class="mt-2 overflow-x-auto rounded-md bg-zinc-900 p-3 font-mono text-[11px] leading-relaxed text-zinc-100"><code>&lt;flux:field&gt;
    &lt;flux:label&gt;Hero badge&lt;/flux:label&gt;
    &lt;flux:input wire:model="settings.theme_first_one_hero_badge" /&gt;
&lt;/flux:field&gt;</code></pre>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">2. Read it in your theme views</p>
                    <pre class="overflow-x-auto rounded-md bg-zinc-900 p-3 font-mono text-[11px] leading-relaxed text-zinc-100"><code>{{-- inside home.blade.php --}}
@php($badge = \App\Models\Setting::get('theme_first_one_hero_badge'))
@if ($badge) &lt;span&gt;{{ $badge }}&lt;/span&gt; @endif</code></pre>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">3. Stored in the database</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Values persist in the
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">settings</code>
                        table under the
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">theme_&lt;slug&gt;_*</code>
                        key format and survive theme switching — each theme keeps its own values. Values are read with
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Setting::get()</code>
                        and written via
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Setting::set()</code>.
                    </p>
                </div>

                <p class="flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50/70 p-4 text-xs text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">
                    <flux:icon.light-bulb class="mt-0.5 size-4 shrink-0" />
                    <span>
                        For a full walkthrough of theme structure, packages and install rules, open the
                        <a href="{{ asset('docs/theme-builder-guide.pdf') }}" target="_blank"
                            class="font-semibold text-blue-700 underline decoration-blue-300 underline-offset-2 hover:decoration-blue-500 dark:text-blue-300">
                            Theme Builder Guide PDF</a>.
                    </span>
                </p>
            </div>

            <div class="flex items-center justify-end border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                <flux:button variant="primary" size="sm" @click="showThemeGuide = false">Got it</flux:button>
            </div>
        </div>
    </div>

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

    <livewire:admin.media-library.picker-modal key="theme-settings-picker-modal" />
</div>