{{--
    Default theme settings — bound to the Theme Settings screen (Admin →
    Theme Settings) via wire:model="settings.theme_{slug}_*". Values persist to
    this theme's own theme.json beside this file and can be read anywhere with
        \App\Support\ThemeSettings::text('default', 'theme_default_intro_heading')
    See default/home.blade.php for live usage.

    This theme owns only the intro block, so it is the one theme whose settings
    screen shows no section menu: a sidebar holding a single "Intro" entry is
    navigation that leads nowhere. The menu is still wired up and switches on
    itself the moment a second section is added to $sections below.

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
    </div>
</div>
