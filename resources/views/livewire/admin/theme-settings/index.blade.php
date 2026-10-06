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

    {{-- Next to Install rather than inside the upload modal: building a theme
         from scratch and adding someone else's are two different jobs, and only
         one of them starts with a zip. --}}
    <flux:button variant="primary" size="sm" icon="plus"
        onclick="window.dispatchEvent(new CustomEvent('open-theme-create'))">
        New Theme
    </flux:button>

    <flux:button variant="outline" size="sm" icon="arrow-top-right-on-square" href="{{ $frontendUrl }}" target="_blank">
        View Public Site
    </flux:button>
@endpush

<div class="space-y-5" x-on:open-theme-install.window="$wire.openInstallModal()" x-on:open-theme-create.window="$wire.openCreateModal()">
    {{-- ── Site Design ──
         The preview grid, and the only place the theme is picked: each card is a
         radio bound to the same `settings.site_theme` the storefront reads, so
         choosing here is choosing the live site, not a separate "editing" state.
         Kept expanded because it holds that selector. --}}
    <x-admin-section-card plain header-border="border-zinc-100" icon="swatch" title="Site Design"
        description="Preview each installed theme, read what it ships with, and pick the one to use — changes apply once you save."
        collapsible :collapsed="false">

        {{-- This card is where a theme is chosen and installed, so the question it
             draws is "how do I add one" — answered by the guide's creation
             checklist. The Theme Settings card below asks a different question and
             points at the rest of the same section; scroll-mt-24 on the target keeps
             the heading clear of the sticky header. --}}
        <x-slot:titleActions>
            <a href="{{ route('admin.developer-guide') }}#create-a-theme"
                title="How to create a theme — open the Developer Guide"
                aria-label="How to create a theme — open the Developer Guide"
                class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                <flux:icon.information-circle class="size-4" />
            </a>
        </x-slot:titleActions>

        {{-- Download failures have nowhere of their own to go now that the actions live
             in the card menus: the menu closes on click-outside, so a message
             rendered inside it would be dismissed before it could be read. Sits
             above the grid instead, where the click that caused it happened. --}}
        @error('themes')
            <p class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50/70 p-3 text-xs font-medium text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400">
                <flux:icon.exclamation-triangle class="mt-px size-4 shrink-0" />
                {{ $message }}
            </p>
        @enderror

        {{-- Three columns only once there is genuinely room for them. The sidebar
             eats a fixed 256px, so counting viewport width overstates what the grid
             gets: at 1280px (xl) a three-up row leaves each card ~320px, and the
             footer below stops fitting title, version, badge and tags on one line.
             2xl is the first breakpoint where three cards are actually comfortable,
             so a laptop gets two roomy cards rather than three squeezed ones. --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-3">
            @foreach ($themes as $slug => $label)
                @php
                    $selected = ($settings['site_theme'] ?? null) === $slug;
                    $preview = $previews[$slug] ?? $previews['default'];
                @endphp

                <label
                    class="group relative flex cursor-pointer flex-col overflow-hidden rounded-xl border bg-white text-left transition-all duration-150
                        @if ($slug === $activeTheme)
                            {{-- The live theme gets a glow rather than a flat ring:
                                 it is the one card in this grid that is actually
                                 serving the site right now, and that has to read at
                                 a glance from across the room — a 2px outline looks
                                 the same as an unsaved selection did. --}}
                            border-primary shadow-[0_0_0_1px_var(--color-primary),0_0_18px_-2px_color-mix(in_oklab,var(--color-primary),transparent_45%)]
                        @elseif ($selected)
                            {{-- Picked but not saved yet: a plain ring, so it never
                                 gets mistaken for the live theme above. --}}
                            border-primary ring-2 ring-primary/25
                        @else
                            border-zinc-200 hover:border-zinc-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800/40 dark:hover:border-zinc-500
                        @endif">

                    <input type="radio" name="site_theme" value="{{ $slug }}"
                        wire:model.live="settings.site_theme" class="sr-only">

                    {{-- Browser chrome --}}
                    <div class="flex items-center gap-1.5 bg-zinc-50 px-3 py-2 dark:bg-zinc-800/70">
                        <span class="size-2 rounded-full bg-rose-400"></span>
                        <span class="size-2 rounded-full bg-amber-400"></span>
                        <span class="size-2 rounded-full bg-emerald-400"></span>
                        <span class="ml-2 flex-1 truncate rounded bg-white px-2 py-0.5 text-[9px] font-medium text-zinc-400 dark:bg-zinc-800">
                            {{ $slug }} · codeware.test
                        </span>
                    </div>

                    {{-- Preview pane. h-44 is a fixed height on purpose — every theme
                         has to preview at the same size or the row of cards compares
                         three different-looking screenshots instead of three themes. --}}
                    <div class="relative h-44 shrink-0 overflow-hidden {{ $preview['bg'] }} p-2.5 dark:bg-zinc-900">
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
                                        <div class="flex flex-col items-center gap-1 rounded-md bg-white p-1.5">
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

                    {{-- Footer. mt-auto pins it to the bottom of the card, so the
                         footers of the cards in a row sit on one line instead of
                         floating at whatever height their own content happened to
                         end — cards in the same grid row stretch to the tallest, and
                         without this the short ones leave a gap under the text. --}}
                    <div class="mt-auto flex items-center gap-3 px-3.5 py-2.5">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $label }}</p>
                                @if (filled($themeCards[$slug]['manifest']['version']))
                                    <span class="shrink-0 font-mono text-[10px] text-zinc-400">v{{ $themeCards[$slug]['manifest']['version'] }}</span>
                                @endif
                                @php
                                    $info = $themeCards[$slug]['manifest'];
                                    $tip = $info['description'];
                                @endphp
                                @if (filled($tip))
                                <flux:tooltip :content="$tip" class="shrink-0">
                                    <button type="button" x-on:click.prevent.stop aria-label="About {{ $label }}"
                                        class="flex size-5 items-center justify-center rounded-full text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                        <flux:icon.information-circle class="size-4" />
                                    </button>
                                </flux:tooltip>
                                @endif
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
                        @if ($themeCards[$slug]['manifest']['tags'] !== [])
                            {{-- Capped rather than wrapped. Ecommerce declares five tags,
                                 and on a narrow card wrapping them pushed the footer to
                                 three lines and threw the row out of alignment. What is
                                 left of the count is said once, instead. --}}
                            <div class="hidden shrink-0 items-center gap-1 lg:flex">
                                @foreach (array_slice($themeCards[$slug]['manifest']['tags'], 0, 2) as $tag)
                                    <span class="rounded-md bg-zinc-50 px-1.5 py-0.5 font-mono text-[10px] text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ $tag }}</span>
                                @endforeach
                                @if (count($themeCards[$slug]['manifest']['tags']) > 2)
                                    <span class="font-mono text-[10px] text-zinc-400">+{{ count($themeCards[$slug]['manifest']['tags']) - 2 }}</span>
                                @endif
                            </div>
                        @endif
                        @if (! $themeCards[$slug]['hasRoutes'])
                            {{-- On the card as well as in the panel below, because
                                 the radio right next to it is what picks the
                                 theme, and this is the last screen before the
                                 site stops resolving. The path is in the
                                 tooltip rather than a sentence, because where
                                 the file goes is the only thing the owner
                                 needs from it — and a theme created from here
                                 keeps it beside its own templates. --}}
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400"
                                title="This theme ships no route file, so it has no pages of its own — put one at themes/{{ $slug }}/routes/web.php. Selecting it will 404 the whole site.">
                                <flux:icon.exclamation-triangle class="size-3.5" />
                            </span>
                        @endif
                    </div>

                    {{-- Per-card actions. There is no tick here on purpose: the live theme already
                         announces itself with the glowing border above, and a second
                         mark saying "selected" made the card say the same thing twice
                         while the thing worth knowing — which one is actually live —
                         was the one carrying the smaller badge.

                         The three dots sit inside the <label>, so every click has to
                         stop propagation or it would also toggle the radio and
                         change the site theme as a side effect of opening a menu.
                         Click-away closes it; Escape does too. --}}
                    <div class="absolute right-2 top-2" x-data="{ open: false }"
                        x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
                        <button type="button" x-on:click.prevent.stop="open = ! open"
                            aria-label="Actions for {{ $label }}" title="Theme actions"
                            class="flex size-6 items-center justify-center rounded-full bg-white/80 text-zinc-500 opacity-0 shadow-sm backdrop-blur transition group-hover:opacity-100 focus:opacity-100 hover:bg-white hover:text-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-white"
                            :class="open && 'opacity-100'">
                            <flux:icon.ellipsis-horizontal class="size-4" />
                        </button>

                        <div x-show="open" x-cloak x-on:click.prevent.stop
                            class="absolute right-0 z-20 mt-1 w-56 overflow-hidden rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                            @php $blocked = \App\Support\Themes::undeletableBecause($slug); @endphp

                            <button type="button" wire:click="downloadTheme('{{ $slug }}')"
                                wire:loading.attr="disabled" wire:target="downloadTheme('{{ $slug }}')"
                                class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 disabled:opacity-50 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <flux:icon.arrow-down-tray class="size-4 shrink-0 text-zinc-400" />
                                <span class="flex-1">Download as zip</span>
                                <span wire:loading.remove wire:target="downloadTheme('{{ $slug }}')"
                                    class="font-mono text-[10px] text-zinc-400">.zip</span>
                                <span wire:loading wire:target="downloadTheme('{{ $slug }}')"
                                    class="text-[10px] text-zinc-400">Preparing…</span>
                            </button>

                            {{-- Delete, and the reason when there isn't one. Both come from
                                 Themes::undeletableBecause(), the same call deleteTheme()
                                 refuses on, so the menu cannot offer an action the server
                                 would only reject. A bundled theme, the live theme and
                                 the last one installed are all kept. --}}
                            <div class="my-1 border-t border-zinc-100 dark:border-zinc-800"></div>

                            @if ($blocked)
                                <span class="block px-3 py-2 text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400"
                                    title="{{ $blocked }}">
                                    <span class="font-medium text-zinc-600 dark:text-zinc-300">Delete theme</span> — {{ $blocked }}
                                </span>
                            @else
                                <button type="button" wire:click="confirmDelete('{{ $slug }}')"
                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-xs font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                                    <flux:icon.trash class="size-4 shrink-0" />
                                    <span class="flex-1">Delete theme</span>
                                    <span class="text-[10px] text-zinc-400">and its files</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
    </x-admin-section-card>

    {{-- ── Delete Theme modal ────────────────────────────────────────────────
         A modal rather than the one-line wire:confirm the Plugins screen uses,
         because deleting a theme folder takes things with it that a single
         sentence does not mention: its settings (they live in its theme.json),
         its templates, and the migrations it already ran — and none of that comes
         back from a trash folder that does not exist. --}}
    @if ($showDeleteModal && $themeToDelete)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/50 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $deleteBlockedBy ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' : 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400' }}">
                        <flux:icon.{{ $deleteBlockedBy ? 'exclamation-triangle' : 'trash' }} class="size-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $deleteBlockedBy ? 'This theme cannot be deleted' : 'Delete this theme?' }}
                        </h3>

                        @if ($deleteBlockedBy)
                            <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $deleteBlockedBy }}</p>
                        @else
                            <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                                <strong class="font-semibold">{{ \App\Support\Themes::manifest($themeToDelete)['name'] }}</strong>
                                and every file in <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[11px] dark:bg-zinc-800">themes/{{ $themeToDelete }}/</code>
                                will be removed. That includes:
                            </p>
                            <ul class="mt-2 list-inside list-disc space-y-1 text-xs leading-relaxed text-zinc-600 dark:text-zinc-300">
                                <li>its templates, controllers, database files and <code class="font-mono text-[10px]">public/</code> assets</li>
                                <li>its <code class="font-mono text-[10px]">routes/web.php</code>, so it stops being a site</li>
                                <li>its settings — they are stored in its own <code class="font-mono text-[10px]">{{ \App\Support\ThemeSettings::FILE }}</code>, which goes with the folder</li>
                            </ul>
                            <p class="mt-2 rounded-lg bg-amber-50 p-2.5 text-xs leading-relaxed text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                                Any database migrations it already ran stay applied. Download it first if you might want it back.
                            </p>
                        @endif
                    </div>
                </div>

                @error('themeToDelete')
                    <p class="mt-3 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="mt-5 flex justify-end gap-2">
                    <flux:button variant="ghost" size="sm" wire:click="closeDeleteModal">
                        {{ $deleteBlockedBy ? 'Close' : 'Cancel' }}
                    </flux:button>

                    @unless ($deleteBlockedBy)
                        <flux:button variant="danger" size="sm" wire:click="deleteTheme" wire:loading.attr="disabled">
                            <span wire:loading.remove>Delete theme</span>
                            <span wire:loading>Deleting…</span>
                        </flux:button>
                    @endunless
                </div>
            </div>
        </div>
    @endif

    {{-- ── Theme's own settings ──────────────────────────────────────────────
         If the selected theme ships a settings.blade.php at its root, render it
         inline (its fields bind to settings.theme_{slug}_* keys, which this
         component hydrates from the theme's own theme.json on mount and writes
         back to it on save).

         The fields and the file are deliberately separate things. The form is
         declared by the theme's settings.blade.php, so it renders whether or not
         the theme has a theme.json - and when the file is missing, that is what
         this card has to say out loud, because otherwise the owner edits a form
         full of blank fields, presses Save, and concludes the panel threw their
         settings away rather than that there was nowhere to put them. --}}

    @if ($selectedHasSettings)
        <x-admin-section-card plain header-border="border-zinc-100" icon="adjustments-horizontal" title="Theme Settings"
            description="Everything the {{ $selectedSlug }} theme defines for itself. Stored in its own {{ \App\Support\ThemeSettings::FILE }} inside the theme folder, not in the database.">
            <x-slot:titleActions>
                {{-- Points into the Developer Guide rather than opening a copy of
                     it. Everything this button used to explain - how a theme
                     declares settings, where they are stored, how a theme folder
                     is laid out, why the routes file matters - is answered once in
                     the guide, so the modal was a second copy to keep true, and the
                     first one to go stale.

                     #theme-settings rather than the top of the Themes section: this
                     card is about the settings themselves, and the guide answers
                     that in three parts in order - where a setting lives, how to
                     read it, how to declare a new one - which is why those are
                     grouped under that one anchor.

                     A plain anchor rather than wire:navigate: the guide is another
                     page entirely, and a full load is what actually honours the
                     fragment and lands on the heading. --}}
                <a href="{{ route('admin.developer-guide') }}#theme-settings"
                    title="How theme settings work and are declared — open the Developer Guide"
                    aria-label="How theme settings work and are declared — open the Developer Guide"
                    class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                    <flux:icon.information-circle class="size-4" />
                </a>
            </x-slot:titleActions>

            {{-- Right of the header: the file's state, and the button that puts
                 it back. A theme is a folder that can be re-downloaded, replaced
                 or checked out from an older branch, and the file goes with it —
                 so this is a state the owner reaches in the ordinary course of
                 managing themes, not a corruption to report. --}}
            <x-slot:actions>
                @if ($settingsFileExists)
                    <span class="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 font-mono text-[11px] font-medium text-emerald-700 sm:inline-flex dark:bg-emerald-500/10 dark:text-emerald-400"
                        title="{{ $settingsFilePath }}">
                        <flux:icon.document-text class="size-3.5" />
                        {{ $settingsFileCount }} {{ Str::plural('value', $settingsFileCount) }}
                    </span>
                @else
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="createSettingsFile"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Create {{ \App\Support\ThemeSettings::FILE }}</span>
                        <span wire:loading>Creating…</span>
                    </flux:button>
                @endif
            </x-slot:actions>

            @error('settingsFile')
                <p class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50/70 p-3 text-xs font-medium text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400">
                    <flux:icon.exclamation-triangle class="mt-px size-4 shrink-0" />
                    {{ $message }}
                </p>
            @enderror

            @unless ($settingsFileExists)
                <div class="mb-5 flex flex-wrap items-start gap-4 rounded-lg border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                        <flux:icon.document-plus class="size-5" />
                    </span>

                    <div class="min-w-0 flex-1 text-sm leading-relaxed text-amber-800 dark:text-amber-200">
                        <p class="font-semibold">This theme has no {{ \App\Support\ThemeSettings::FILE }} file.</p>
                        <p class="mt-1 text-xs text-amber-700/90 dark:text-amber-300/80">
                            Its name and its settings both live in a
                            <code class="rounded bg-amber-100/70 px-1 py-0.5 font-mono text-[10px] dark:bg-amber-500/15">{{ \App\Support\ThemeSettings::FILE }}</code>
                            inside the theme folder, which means that file can be lost
                            along with the folder - a re-uploaded theme, an older checkout,
                            a deploy that shipped templates but not content. The fields below
                            still render, but there is nowhere to save them until it is back.
                            Create it and they will be written there on the next save.
                        </p>

                        <p class="mt-2 break-all font-mono text-[11px] text-amber-700/80 dark:text-amber-300/70">
                            {{ $settingsFilePath ?? base_path('themes/'.$selectedSlug.'/'.App\Support\ThemeSettings::FILE) }}
                        </p>
                    </div>

                    <flux:button variant="primary" size="sm" icon="plus" wire:click="createSettingsFile"
                        wire:loading.attr="disabled" class="shrink-0">
                        <span wire:loading.remove>Create {{ \App\Support\ThemeSettings::FILE }}</span>
                        <span wire:loading>Creating…</span>
                    </flux:button>
                </div>
            @endunless

            @include(App\Support\Themes::viewNamespace($selectedSlug).'::settings', [
                'themeSlug' => $selectedSlug,
                'settings' => $settings,
            ])
        </x-admin-section-card>
    @endif

    {{-- ── Site Widgets ─────────────────────────────────────────────────────
     Live Chat and Popup are not theme settings. Neither has a single key
     starting with a theme slug, neither is declared by the theme's own
     settings.blade.php, and neither lands in the theme's theme.json — they
     are two site-wide features that happen to live on this screen. Left as
     siblings of the theme's own sections they read as more of the same
     thing, which is the wrong thing to believe about them: editing a theme
     should not imply you are about to change the chat bubble for every
     theme, or a popup that is not part of the theme at all.

     So they get their own parent section. It carries the boundary on its
     own — its heading, and the outlined cards nested under it — so nothing
     is wedged between it and Theme Settings; the gap a separator would have
     occupied is not worth a strip of empty page halfway down. --}}
<x-admin-section-card plain icon="squares-2x2" title="Site Widgets"
        description="Features that sit on the public site on their own, kept apart from the theme above because no theme sets them and switching theme does not change them.">

        <div class="space-y-5">

    {{-- ── Live Chat Widget ───────────────────────────────────────────────
         Collapsed on open, like Popup: the colour picker and its mockup are a
         niche setting, and this page already carries the theme picker, the
         selected theme's own sections, then Popup. An always-open chat block in
         the middle of that pushes the theme's settings below the fold for
         anyone who came here to edit the theme.

         The enable switch stays in the header, so whether chat is on is still
         readable without expanding anything — this folds the controls, not the
         state. The card component puts @click.stop on the actions slot, so
         flipping that switch does not also fold the card it sits in. --}}
    <x-admin-section-card header-border="border-zinc-100" icon="chat-bubble-left-right" title="Live Chat Widget"
        description="The support chat bubble on the public site. Visitors verify with an email code before chatting."
        collapsible :collapsed="true">

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

                    <p class="-mt-2 flex items-start gap-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                        <flux:icon.information-circle class="mt-px size-4 shrink-0 text-zinc-400" />
                        <span>
                            A theme that ships no
                            <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ \App\Support\ThemeSettings::FILE }}</code>
                            gets one created for it on the spot, seeded with every field it declares — so
                            you can install a theme and go straight to filling it in. One
                            <em>cannot</em> come from inside the zip: a theme's routes are code the app
                            registers, not files it may read out of a folder, so add
                            <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">routes.php</code>
                            to the theme folder before installing — beside its templates, where
                            <strong>New Theme</strong> puts it.
                        </span>
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
                    <div class="mt-3">
                        <flux:input type="password" wire:model="installPassword" label="Confirm your password" autocomplete="current-password" />
                    </div>

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


    {{-- ── New Theme modal ───────────────────────────────────────────────────
         Writes a starter folder rather than a file, so this says what lands on
         disk instead of where to put it: the folder is the deliverable, and the
         screen says "you can open it and edit it" rather than a path the owner
         then has to go and find. --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
                @click.away="$wire.closeCreateModal()">

                <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-700">
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">New Theme</h3>
                    <button wire:click="closeCreateModal"
                        class="rounded p-1 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid gap-5 p-6">
                    <p class="text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Creates a theme folder you can open and edit: every page template it could
                        ever need, the header and footer they share, its own 404, its stylesheet,
                        its settings screen and a
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">routes.php</code>
                        to write your controllers into — all inside
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">themes/&lt;slug&gt;/</code>,
                        so the one folder can be zipped and handed to someone else whole.
                    </p>

                    <p class="-mt-2 flex items-start gap-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                        <flux:icon.information-circle class="mt-px size-4 shrink-0 text-zinc-400" />
                        <span>
                            Nothing goes live. The theme shows up on the picker above as a card to
                            choose from, and your current design stays on the public site until you
                            pick it and save.
                        </span>
                    </p>

                    <flux:field>
                        <flux:label>Name<x-field-hint text="What the theme is called in the admin." /></flux:label>
                        <flux:input wire:model="newName" placeholder="e.g. Aurora" />
                        @error('newName')
                            <flux:error name="newName" />
                        @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Slug<x-field-hint text="The folder name. Edit it before creating — it cannot be changed later without renaming the folder." /></flux:label>
                        <flux:input wire:model.live.debounce.400ms="newSlug" class="font-mono"
                            x-on:input="$wire.set('slugEdited', true)" placeholder="aurora" />
                        @error('newSlug')
                            <flux:error name="newSlug" />
                        @enderror
                    </flux:field>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Version</flux:label>
                            <flux:input wire:model="newVersion" placeholder="1.0.0" />
                            @error('newVersion')
                                <flux:error name="newVersion" />
                            @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label>Author<x-field-hint text="Optional." /></flux:label>
                            <flux:input wire:model="newAuthor" placeholder="{{ config('app.name') }}" />
                            @error('newAuthor')
                                <flux:error name="newAuthor" />
                            @enderror
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Description<x-field-hint text="Optional. One line, shown on the theme card." /></flux:label>
                        <flux:textarea wire:model="newDescription" class="h-20"
                            placeholder="A single-column storefront with a dark hero." />
                        @error('newDescription')
                            <flux:error name="newDescription" />
                        @enderror
                    </flux:field>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <flux:button variant="ghost" size="sm" wire:click="closeCreateModal">Cancel</flux:button>
                    <flux:button variant="primary" size="sm" wire:click="createTheme" wire:loading.attr="disabled">
                        <span wire:loading.remove>Create Theme</span>
                        <span wire:loading>Creating…</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif


    {{-- ── Save bar ──
         No upward shadow: it drew a dark line across the bottom of the form,
         reading as the card's footer edge rather than as the bar floating over
         the page. The border is what separates it from the panel behind it. --}}
    <div
        class="sticky bottom-0 z-10 flex flex-col gap-3 rounded-[5px] border border-zinc-200 bg-white/95 px-5 py-3.5 backdrop-blur dark:border-zinc-700 dark:bg-zinc-800/90 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-zinc-400">
            Your changes apply to the public site once saved.
        </p>
        <div class="flex items-center gap-2.5">
            <flux:button variant="outline" wire:click="resetSettings" size="sm">
                Discard
            </flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" size="sm">
                <span wire:loading.remove>Save Changes</span>
                <span wire:loading>Saving…</span>
            </flux:button>
        </div>
    </div>

    <livewire:admin.media-library.picker-modal key="theme-settings-picker-modal" />
</div>
