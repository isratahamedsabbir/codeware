{{--
    Default theme settings — bound to the Theme Settings screen (Admin →
    Theme Settings) via wire:model="settings.theme_{slug}_*". Values persist to
    this theme's own theme.json beside this file and can be read anywhere with
        \App\Support\ThemeSettings::text('default', 'theme_default_intro_heading')
    See default/home.blade.php for live usage.

    This theme owns only the intro block, so before Typography it was the one
    theme whose settings screen showed no section menu: a sidebar holding a
    single "Intro" entry is navigation that leads nowhere. The menu is still
    wired up and switches on itself the moment a second section is added to
    $sections below.

    The wire:key matches the other two themes and is load-bearing. All three
    partials are swapped into one slot by a plain @include on the Theme Settings
    screen; an unkeyed root would be morphed in place and Alpine would keep
    whichever x-data it parsed first, so arriving here from portfolio would leave
    `tab` pointing at a section that does not exist and the screen would come up
    blank. See portfolio/settings.blade.php for the full account.
--}}
@php
    $sections = [
        'intro' => ['Intro', 'megaphone'],
        'typography' => ['Typography', 'bars-3-bottom-left'],
    ];

    // One entry means there is nothing to choose between.
    $showMenu = count($sections) > 1;
@endphp
<div
    wire:key="theme-settings-{{ $themeSlug }}"
    x-data="{
        tab: (() => { try { return localStorage.getItem('theme-default-tab') || 'intro' } catch (e) { return 'intro' } })(),
        open(name) { this.tab = name; try { localStorage.setItem('theme-default-tab', name) } catch (e) {} },
    }"
    class="grid gap-5 {{ $showMenu ? 'lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-6' : 'grid-cols-1' }}"
>
    @if ($showMenu)
        <x-admin-settings-nav class="lg:sticky lg:top-14 lg:self-start" :tabs="$sections" />
    @endif

    <div @class(['min-w-0 space-y-5', 'x-cloak' => $showMenu])>
        {{-- Bare panel: the admin "Theme Settings" card supplies the surface behind it,
         so a card here would be a white box on a white box. --}}
        <section role="tabpanel" x-show="tab === 'intro'">
            <div class="grid grid-cols-1 gap-5">
                <flux:field>
                    <flux:label>Intro Heading<x-field-hint text="The headline rendered on the public homepage." /></flux:label>
                    <flux:input wire:model="settings.theme_default_intro_heading" placeholder="Welcome to {{ config('app.name') }}" />
                </flux:field>

                <flux:field>
                    <flux:label>Intro Text<x-field-hint text="The supporting line under the heading." /></flux:label>
                    <flux:textarea wire:model="settings.theme_default_intro_text" class="h-24" placeholder="A short description of the site." />
                </flux:field>
            </div>
        </section>
        <section role="tabpanel" x-show="tab === 'typography'">
            <div class="grid grid-cols-1 gap-5">
                {{-- The face this theme's pages render in. "Theme default" leaves
                     the theme looking the way it was designed, which is what an
                     untouched field means; anything else overrides it for this
                     theme only. See App\Support\ThemeFont. --}}
                <x-admin-font-picker
                    model="settings.theme_default_font"
                    :value="$settings['theme_default_font'] ?? ''"
                    own-face="plus-jakarta"
                    own-label="Plus Jakarta Sans"
                    hint="Applies to this theme's public pages only. Every theme keeps its own typeface until you say otherwise."
                    description="Theme default keeps Plus Jakarta Sans, the font this theme ships with. Anything else replaces it for this theme alone — the other themes are untouched." />
            </div>
        </section>
    </div>
</div>

@fluxScripts
