@php
    use App\Support\Features;
@endphp

{{-- Sticky contents rail + scrolling body. Above lg the grid is a single column
     and the rail sits on top of the page as an ordinary block, so it flows as
     two columns of links there instead of a twelve-item stack pushing the guide
     itself off the screen. --}}
<div class="grid items-start gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">

    {{-- Contents --}}
    <aside class="lg:sticky lg:top-[4.5rem] lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto"
        x-data="{
            active: @js('doc-'.array_key_first($sections)),
            spy() {
                // The rail and the sections are sibling grid cells, so the
                // sections have to be looked up from the shared parent - querying
                // this.$root only ever finds the rail's own markup.
                const sections = [...this.$root.parentElement.querySelectorAll('[data-doc-section]')];

                if (! sections.length) return;

                // A section counts as current once its heading has crossed this
                // line, so the highlight tracks the heading rather than whatever
                // pixel happens to be at the top of the screen.
                const line = () => window.innerHeight * 0.25;

                let queued = false;

                const update = () => {
                    queued = false;

                    const passed = sections.filter((el) => el.getBoundingClientRect().top <= line());
                    const atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;

                    // The bottom-of-page case has no section past the line - the
                    // last one is simply short - so without this the last entry in
                    // the rail could never light up.
                    this.active = atBottom
                        ? sections[sections.length - 1].id
                        : (passed.length ? passed[passed.length - 1].id : sections[0].id);
                };

                const onScroll = () => {
                    if (queued) return;
                    queued = true;
                    requestAnimationFrame(update);
                };

                update();

                // No teardown: this page is a full-page Livewire render, so the
                // document outliving the listener is the normal case anyway.
                window.addEventListener('scroll', onScroll, { passive: true });
                window.addEventListener('resize', onScroll);
            }
        }"
        x-init="spy()">

        <nav class="rounded-[5px] border border-zinc-200 bg-white/70 p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/40">
            <p class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                Contents
            </p>

            <ul class="grid grid-cols-2 gap-x-2 gap-y-0.5 lg:block lg:space-y-0.5">
                @foreach ($sections as $id => $meta)
                    @php $anchor = 'doc-'.$id; @endphp
                    <li>
                        <a href="#{{ $anchor }}"
                            :class="active === '{{ $anchor }}'
                                ? 'bg-primary/10 text-primary font-semibold'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-700/60 dark:hover:text-zinc-100'"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors">
                            <span class="w-4 shrink-0 font-mono text-[11px] text-zinc-400 dark:text-zinc-500">
                                {{ $loop->iteration }}
                            </span>
                            <x-dynamic-component :component="'flux::icon.'.$meta['icon']"
                                class="size-3.5 shrink-0 opacity-70" />
                            <span class="truncate">{{ $meta['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </aside>

    {{-- Guide --}}
    <div class="min-w-0 space-y-6">

        {{-- Header --}}
        <div class="overflow-hidden rounded-[5px] bg-linear-to-br from-primary to-secondary text-white shadow-sm">
            <div class="flex items-start gap-5 p-8">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-white/10">
                    <flux:icon.book-open class="size-7" />
                </span>
                <div class="min-w-0">
                    <flux:heading size="xl" class="text-white!">Developer Guide</flux:heading>
                    <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-white/80">
                        How this application is put together &mdash; how to build and use plugins and themes, how
                        features and settings work, and how Pages, their inner content and the rest of the CMS
                        fit together. Written against this codebase, not in the abstract.
                    </p>
                </div>
            </div>

            {{-- Live readouts. These are read from the app on every render rather
                 than typed in, so the numbers cannot go stale. --}}
            <dl class="grid grid-cols-2 gap-px border-t border-white/10 bg-white/10 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    ['Laravel', app()->version()],
                    ['PHP', PHP_VERSION],
                    ['Active theme', $activeTheme],
                    ['Themes', count($themes)],
                    ['Plugins', count($plugins)],
                    ['Features', count(Features::ALL)],
                ] as [$k, $v])
                    <div class="bg-primary/90 px-4 py-2.5">
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-white/60">{{ $k }}</dt>
                        <dd class="truncate font-mono text-xs font-semibold text-white">{{ $v }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- 1. Plugins --}}
    <x-admin-doc-section id="plugins" icon="puzzle-piece" title="1. Plugins"
        description="app/Support/Plugins.php &middot; app/Providers/PluginServiceProvider.php &middot; plugins/">
        <p>
            A plugin is a self-contained folder under <span class="font-mono text-xs">plugins/</span>. It is
            &ldquo;installed&rdquo; when the folder exists with the two required files, and
            &ldquo;active&rdquo; when the admin has switched it on &mdash; active slugs live in the
            <span class="font-mono text-xs">plugins_active</span> setting. A plugin flagged
            <span class="font-mono text-xs">"default": true</span> ships with the app, is always active, and
            cannot be removed.
        </p>

        <x-admin-doc-code label="Required folder layout" file="plugins/{slug}/" lang="text">
plugin.json        required - manifest (name, version, description, author, icon, default, settings?)
index.blade.php    required - the plugin's own management screen
header.blade.php   optional - an icon in the admin top bar
routes.php         optional - admin routes, mounted at /plugins/{slug}/… (admin.plugins.{slug}.*)
migrations/        optional - loaded while the plugin is active
*.blade.php        optional - any other blade at the plugin root, reachable as plugin-{slug}::name
        </x-admin-doc-code>

        <x-admin-doc-note tone="warning" title="There is no views/ directory in a plugin">
            <span class="font-mono text-[11px]">View::addNamespace()</span> is pointed at the plugin folder
            itself, so <span class="font-mono text-[11px]">header.blade.php</span> sits beside
            <span class="font-mono text-[11px]">index.blade.php</span> at the root. Putting views in a
            <span class="font-mono text-[11px]">views/</span> subfolder &mdash; the reflex from a
            Laravel package &mdash; makes them unreachable as
            <span class="font-mono text-[11px]">plugin-{slug}::name</span>.
        </x-admin-doc-note>

        <x-admin-doc-code label="Manifest" file="plugins/clock/plugin.json" lang="json">
{
    "name": "Clock",
    "version": "1.0.0",
    "description": "Adds a clock to the admin header.",
    "author": "Codeware",
    "icon": "clock",
    "default": true,
    "settings": {
        "enabled": false,
        "style": "analog",
        "format": "12",
        "seconds": true
    }
}
        </x-admin-doc-code>

        <p>
            The <span class="font-mono text-xs">settings</span> block declares the plugin's own fields and
            their defaults. Only those keys are ever stored, as JSON in the
            <span class="font-mono text-xs">plugin_{slug}_settings</span> setting &mdash;
            <span class="font-mono text-xs">Plugins::settings()</span> merges saved values over the declared
            defaults and <span class="font-mono text-xs">saveSettings()</span> drops anything not declared.
            The icon must be a real Flux icon; an unknown one falls back to
            <span class="font-mono text-xs">puzzle-piece</span>.
        </p>

        <p>
            <span class="font-mono text-xs">Plugins::indexView()</span> hands the plugin's
            <span class="font-mono text-xs">index.blade.php</span> to
            <span class="font-mono text-xs">Admin\Plugins\Show</span>, which renders it inside
            <span class="font-mono text-xs">layouts.admin</span> with two things in scope:
            <span class="font-mono text-xs">$plugin</span> (the manifest array) and
            <span class="font-mono text-xs">values.*</span> (the saved settings). Because the component
            saves on every <span class="font-mono text-xs">values</span> change, a field bound with
            <span class="font-mono text-xs">wire:model.live</span> persists as you type.
        </p>

        <x-admin-doc-code label="Reading settings inside a plugin" file="plugins/clock/header.blade.php">
@verbatim
@php
    $clock = \App\Support\Plugins::settings('clock');
@endphp
@if ($clock['enabled'])
    &lt;flux:icon.clock class="size-5" /&gt;
@endif
@endverbatim
        </x-admin-doc-code>

        <p>
            <span class="font-mono text-xs">PluginServiceProvider</span> registers the
            <span class="font-mono text-xs">plugin-{slug}</span> view namespace for every installed plugin,
            then &mdash; for active ones only &mdash; loads its migrations and mounts its
            <span class="font-mono text-xs">routes.php</span> on the admin host under
            <span class="font-mono text-xs">/plugins/{slug}</span>, named
            <span class="font-mono text-xs">admin.plugins.{slug}.*</span>, behind
            <span class="font-mono text-xs">auth</span> + <span class="font-mono text-xs">admin</span> +
            <span class="font-mono text-xs">activity-log</span> +
            <span class="font-mono text-xs">can:access-admin-system</span>.
        </p>

        <p>
            The sidebar's <strong>Plugins</strong> dropdown is built at runtime by
            <span class="font-mono text-xs">Plugins::extendMenu()</span>, one entry per active plugin, plus a
            Plugin Settings link. It is deliberately not stored in <span class="font-mono text-xs">menu_items</span>
            &mdash; the set of plugins changes by dropping a folder in, not by editing the menu.
        </p>

        <x-admin-doc-code label="Creating, installing and packing plugins" file="app/Support/Plugins.php:331">
// Admin -> Plugins -> Plugin Settings -> New Plugin: writes plugins/{slug}/
// with plugin.json, index.blade.php and a commented routes.php
$slug = Plugins::create([
    'name' => 'Clock', 'slug' => 'clock', 'version' => '1.0.0',
    'description' => 'What it does.', 'author' => 'You', 'icon' => 'clock',
]);

// Admin -> Plugins -> Plugin Settings -> Install
$slug = Plugins::installFromZip($upload->getRealPath());

// The download button on a plugin's row - a backup before hand-editing:
$zipPath = Plugins::toZip('calendar');   // one folder at the zip root

// Or activate / deactivate / remove:
Plugins::setActive('calendar', true);
Plugins::delete('calendar');   // refuses for "default": true
        </x-admin-doc-code>

        <p class="text-xs text-zinc-500">
            The installer accepts either a zip holding exactly one plugin folder, or
            <span class="font-mono">plugin.json</span> at the zip root (slug taken from its
            <span class="font-mono">slug</span> or <span class="font-mono">name</span> key). It rejects path
            traversal, caps the package at 2000 files / 50&nbsp;MB, and refuses to overwrite an existing slug.
        </p>

        <p class="text-xs text-zinc-500">
            <strong>New Plugin</strong> on the Plugin Settings screen asks only for the basics and writes a
            starter folder &mdash; <span class="font-mono">plugin.json</span>,
            <span class="font-mono">index.blade.php</span> and a commented
            <span class="font-mono">routes.php</span> &mdash; which the admin then edits by hand before
            activating it. The download button on a plugin's row packs the folder back into that same
            one-folder zip (<span class="font-mono">Plugins::toZip()</span>), so a backup taken before editing
            installs again as-is.
        </p>

        <div>
            <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Installed plugins
            </flux:text>
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-900/40">
                        <tr>
                            <th class="px-3 py-2 font-medium">Slug</th>
                            <th class="px-3 py-2 font-medium">Name</th>
                            <th class="px-3 py-2 font-medium">Version</th>
                            <th class="px-3 py-2 font-medium">Author</th>
                            <th class="px-3 py-2 font-medium">State</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($plugins as $slug => $plugin)
                            <tr>
                                <td class="px-3 py-2 font-mono text-zinc-700 dark:text-zinc-200">{{ $slug }}</td>
                                <td class="px-3 py-2">{{ $plugin['name'] }}</td>
                                <td class="px-3 py-2 font-mono text-zinc-500">{{ $plugin['version'] }}</td>
                                <td class="px-3 py-2 text-zinc-500">{{ $plugin['author'] }}</td>
                                <td class="px-3 py-2">
                                    @if ($plugin['default'])
                                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">default</span>
                                    @elseif ($plugin['active'])
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">active</span>
                                    @else
                                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-800">inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-zinc-500">
                                    No plugins installed. Drop a folder into <span class="font-mono">plugins/</span>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-admin-doc-section>

    {{-- 2. Themes --}}
    <x-admin-doc-section id="themes" icon="swatch" title="2. Themes"
        description="app/Support/Themes.php &middot; app/Support/ThemeSettings.php &middot; themes/">
        <p>
            A theme is a self-contained module in
            <span class="font-mono text-xs">themes/{slug}/</span>, the same shape as a plugin: its routes,
            templates, controllers, database files and public assets are all inside that one folder, so
            the folder is the unit that gets zipped, installed and handed to someone else.
            <span class="font-mono text-xs">ThemeServiceProvider</span> plugs every installed theme in.
        </p>

        <x-admin-doc-code label="Inside a theme module" file="themes/{slug}/" lang="text">
theme.json                manifest + settings values
settings.blade.php        its admin settings screen
routes/web.php            its storefront routes, behind the 'theme' guard
*.blade.php               its templates (partials/_*.blade.php, errors/, account/ ...), view namespace theme-{slug}::
Controllers/              namespace Themes\{Slug}\Controllers, autoloaded - no composer dump
database/migrations/      loaded with the app's own migrations
database/seeders/         namespace Themes\{Slug}\Database\Seeders
public/                   everything the web serves, at /themes/{slug}/...
    css/theme.css         its own Vite entry (omit to inherit the catch-all storefront.css)
    css/, js/, img/       hand-written static files, served as-is
        </x-admin-doc-code>

        <p>
            <strong>New Theme</strong> writes the templates,
            <span class="font-mono text-xs">routes/web.php</span>
            and <span class="font-mono text-xs">public/css/theme.css</span> into the folder. Shared base classes
            (<span class="font-mono text-xs">ThemeController</span> and the
            <span class="font-mono text-xs">Renders*</span> concerns) stay in
            <span class="font-mono text-xs">app/Http/Controllers/Themes/</span>; a theme's own controllers extend them.
        </p>
        <x-admin-doc-note tone="danger" title="There is no template fallback between themes">
            A page the active theme ships no template for <strong>does not exist</strong> on that site. It 404s
            rather than quietly rendering in another theme's design &mdash; and because
            <span class="font-mono text-[11px]">MenuItem::isRenderableByCurrentTheme()</span> checks the same
            rule, a nav link to such a page is dropped instead of being left as a dead end. If you copy a page
            into a new theme, copy its template across too.
        </x-admin-doc-note>

        <p>
            The stylesheet is the one deliberate exception: a theme with no
            <span class="font-mono text-xs">theme.css</span> of its own is styled by the catch-all storefront
            bundle, because bare HTML is a broken storefront.
        </p>

        <x-admin-doc-note tone="warning" title="Customers are a theme's own pages too">
            A theme opts into having customer accounts by shipping the guest pages in its own
            <span class="font-mono text-[11px]">auth/</span> folder &mdash;
            <span class="font-mono text-[11px]">login</span>,
            <span class="font-mono text-[11px]">register</span>,
            <span class="font-mono text-[11px]">forgot-password</span>,
            <span class="font-mono text-[11px]">verify-code</span> (the reset step, which is by emailed
            code rather than a link),
            <span class="font-mono text-[11px]">reset-password</span> &mdash; and the account area itself
            under <span class="font-mono text-[11px]">account/</span>. These resolve through
            <span class="font-mono text-[11px]">Themes::viewOrFail()</span> like any other page, so
            <span class="font-mono text-[11px]">/login</span> on a theme with no
            <span class="font-mono text-[11px]">auth/login.blade.php</span> is a <strong>404</strong>, not
            a shared page in someone else's design &mdash; which is what it used to be, and why a
            portfolio site used to answer that URL with a page badged &ldquo;Admin Panel&rdquo;.
            Only <span class="font-mono text-[11px]">ecommerce</span> ships them today.

            <p class="mt-2">
                The three screens a signed-in customer passes <em>through</em> rather than to &mdash; the
                email-verification notice, the two-factor challenge and the password confirmation
                &mdash; stay shared on purpose, in
                <span class="font-mono text-[11px]">resources/views/pages/auth/</span>: they are steps
                inside a flow, not pages to navigate to, and a theme should not be able to 404 a customer
                halfway through signing in. Each panel keeps its own login entirely, on its own host
                (<span class="font-mono text-[11px]">route('admin.login')</span>), so nothing here can
                lock an administrator out of the site.
            </p>
        </x-admin-doc-note>

        {{-- The Theme Settings screen's own destination. Grouped as one block because
             that screen's question is answered in three parts that only make sense
             in order - where a setting lives, how to read it, how to declare a new
             one - and a jump target in the middle of them is worse than none. Kept
             separate from the folder layout above it, which is about what a theme
             *is*, and from the creation checklist below, which is about adding one. --}}
        <div id="theme-settings" class="scroll-mt-24">
            <x-admin-doc-code label="theme.json - manifest and settings in one file" file="themes/default/theme.json">
{
    "name": "Default",
    "sn": 1,
    "description": "The minimal built-in theme.",
    "version": "1.0.0",
    "author": "Codeware",
    "tags": ["landing", "minimal"],
    "no_index": true,
    "theme_default_intro_heading": "",
    "theme_default_intro_text": "",
    "theme_default_font": ""
}
        </x-admin-doc-code>

        <p>
            <span class="font-mono text-xs">MANIFEST_KEYS</span> (&nbsp;name, description, version, author,
            tags, no_index, sn&nbsp;) describe the theme; <em>everything else in the file is a setting</em>.
            Theme settings live here rather than in the <span class="font-mono text-xs">settings</span> table
            so that a theme is a folder you can zip, hand to someone else and re-upload with its content
            intact. Keys keep their <span class="font-mono text-xs">theme_{slug}_*</span> names.
        </p>

        <x-admin-doc-code label="Reading a theme setting" file="in any theme template">
@verbatim
{{ theme_setting('hero_title') }}                  {# active theme, short form #}
{{ theme_setting('hero_title', 'Fallback') }}
{{ theme_setting('hero_title', '', 'portfolio') }}  {# a named theme #}
{{ theme_color('brand_color', '#0f172a') }}
@foreach (theme_rows('projects') as $project)
    &lt;h3&gt;{{ $project['title'] }}&lt;/h3&gt;
@endforeach
@endverbatim
        </x-admin-doc-code>

        <p>
            Both the short and long key forms work everywhere:
            <span class="font-mono text-xs">hero_title</span> resolves to
            <span class="font-mono text-xs">theme_{active}_hero_title</span>, and a key that already carries
            the prefix is passed through untouched.
        </p>

        <p>
            The rest of the set takes the same optional trailing slug:
            <span class="font-mono text-xs">theme_name()</span> for a display name and
            <span class="font-mono text-xs">theme_manifest()</span> for the whole manifest,
            <span class="font-mono text-xs">theme_json()</span> for the entire
            <span class="font-mono text-xs">theme.json</span> &mdash; manifest fields and settings alike, and
            an empty array rather than an error when the file is missing or unparseable,
            <span class="font-mono text-xs">theme_setting_raw()</span> when you want the stored value as-is
            rather than flattened to a string, and
            <span class="font-mono text-xs">theme_setting_key()</span> for a template that has to build the
            prefixed key itself &mdash; an <span class="font-mono text-xs">id</span>/<span class="font-mono text-xs">for</span>
            pair, a query string, an assertion.
        </p>

        <p>
            The active theme is the <span class="font-mono text-xs">site_theme</span> setting, switched from
            <strong>Admin &rarr; Theme Settings</strong>. That one screen does three jobs: it picks the active
            theme, edits the site-wide keys (<span class="font-mono text-xs">site_theme</span> and the
            homepage copy/imagery), and renders the selected theme's own
            <span class="font-mono text-xs">settings.blade.php</span> inline. It also installs a theme from a
            zip, creates one from its basics with <strong>New Theme</strong>, and can create a missing
            <span class="font-mono text-xs">theme.json</span> from the slug.
        </p>

        <x-admin-doc-code label="A theme's own settings screen" file="themes/portfolio/settings.blade.php">
{{-- scalar fields bind to the settings bag, with the theme prefix in the key --}}
&lt;flux:field&gt;
    &lt;flux:label&gt;Display Name&lt;/flux:label&gt;
    &lt;flux:input wire:model="settings.theme_portfolio_name" /&gt;
&lt;/flux:field&gt;

{{-- lists declare only the key; the add/remove/reorder UI is grown for you --}}
&lt;x-admin-repeatable-fields setting-key="theme_portfolio_projects" :max="12" /&gt;
        </x-admin-doc-code>

        <p>
            Scalars bind to the <span class="font-mono text-xs">$settings</span> bag; lists bind to
            <span class="font-mono text-xs">$repeaters</span> and are discovered by
            <span class="font-mono text-xs">Admin\ThemeSettings\Index</span> parsing this file for
            <span class="font-mono text-xs">setting-key=&quot;theme_*&quot;</span> &mdash; which is also where
            each list's <span class="font-mono text-xs">:max</span> is read from, so the add button and the
            save-time cap cannot disagree.
        </p>

        <p>
            Because the values live in the theme's own file, a portfolio theme can declare content with no
            models and no migrations at all &mdash; projects, experience, skills, testimonials, education and
            certifications are just key/value lists and repeatables declared in
            <span class="font-mono text-xs">settings.blade.php</span> and edited on that single screen.
        </p>

        <p class="text-xs text-zinc-500">
            Theme folders are scanned by <span class="font-mono">Themes::all()</span> and cached for a day
            (<span class="font-mono">themes:all</span>); manifests are re-read when the file's mtime or size
            changes, so a hand-edited <span class="font-mono">theme.json</span> takes effect on the next
            request. <span class="font-mono">ThemeSettings::file()</span> rejects any slug outside
            <span class="font-mono">/^[A-Za-z0-9_-]+$/</span>, so a theme folder name can never be turned into
            a path outside the themes directory.
        </p>
        </div>

        <div>
            <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Installed themes
            </flux:text>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($themes as $slug => $label)
                    @php $manifest = \App\Support\Themes::manifest($slug); @endphp
                    <div @class([
                        'rounded-lg border p-4',
                        'border-primary bg-primary/5' => $slug === $activeTheme,
                        'border-zinc-200 dark:border-zinc-700' => $slug !== $activeTheme,
                    ])>
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $manifest['name'] }}</p>
                            @if ($slug === $activeTheme)
                                <span class="rounded-full bg-primary px-2 py-0.5 text-[11px] font-medium text-white">active</span>
                            @endif
                        </div>
                        <p class="mt-0.5 font-mono text-[11px] text-zinc-400">{{ $slug }}</p>
                        <p class="mt-2 text-xs text-zinc-500">
                            v{{ $manifest['version'] }} &middot; SN {{ $manifest['sn'] }}
                            @if ($manifest['author']) &middot; {{ $manifest['author'] }} @endif
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ([
                                'stylesheet' => \App\Support\Themes::hasStylesheet($slug),
                                'routes' => \App\Support\Themes::routeFileExists($slug),
                                'settings' => \App\Support\Themes::hasSettings($slug),
                                'no_index' => ! \App\Support\Themes::isIndexable($slug),
                            ] as $flag => $on)
                                @if ($on)
                                    <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] text-zinc-500 dark:bg-zinc-800">{{ str_replace('_', ' ', $flag) }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- A jump target of its own, because "how do I create a theme" is a
             different question from "how do theme settings work": the Site Design
             card asks the former and the Theme Settings card asks the latter, so
             each needs to land on its own answer rather than both on the top of
             this section. Anchored on the checklist itself rather than a heading,
             since the guide runs on prose and code blocks with no sub-headings to
             hang an id on. --}}
        <div id="create-a-theme" class="scroll-mt-24">
            <p>
                <strong>New Theme</strong> on the Theme Settings screen writes steps 1&ndash;7 of this
                checklist for you: name it on the form and it creates the folder with a template for every
                route name in <span class="font-mono text-xs">Themes::ROUTE_TEMPLATES</span> &mdash;
                each one a working placeholder &mdash; plus
                <span class="font-mono text-xs">errors/404.blade.php</span>, a header and footer partial
                They share, <span class="font-mono text-xs">public/css/theme.css</span>,
                <span class="font-mono text-xs">settings.blade.php</span>, a commented
                <span class="font-mono text-xs">routes/web.php</span> and a
                <span class="font-mono text-xs">theme.json</span> seeded with the name, version and author
                you typed and the next free serial number. Nothing is activated: it appears on the picker as
                a card, and your current design stays live until you choose it and save.
            </p>

            <x-admin-doc-code label="Building a new theme - the checklist" file="" lang="text">
0. Admin -> Theme Settings -> New Theme, or do the rest by hand
1. mkdir themes/my-theme/
2. theme.json  - name, version, author (tags / no_index / sn optional)
3. one template per route name in Themes::ROUTE_TEMPLATES  - home, page,
   shop, product, category, brand, tag, favorites, blog, post, cart,
   checkout, order-confirmation, account/{dashboard,orders,order,profile}
4. errors/404.blade.php  - otherwise Laravel's shared error page is used
5. partials/_header.blade.php + _footer.blade.php  - shared chrome, underscored so they are the ones a page @includes
6. settings.blade.php  - optional, to expose theme-specific options in the admin
7. public/css/theme.css  - optional, omit to inherit the catch-all storefront.css
8. routes/web.php  - optional, auto-registered behind the 'theme' guard
9. Controllers/, database/, public/css|js  - optional, see "Inside a theme module"
10. Admin -> Theme Settings -> pick it as the active theme
            </x-admin-doc-code>
        </div>
    </x-admin-doc-section>

    {{-- 3. Features --}}
    <x-admin-doc-section id="features" icon="power" title="3. Features"
        description="app/Support/Features.php &middot; app/Models/Feature.php &middot; the features table">
        <p>
            Features are per-deployment on/off switches for whole modules. This panel is reused as a starting
            point across projects, and not every project needs Chat, Blog or the File Manager &mdash; so a
            feature can be turned off without touching code: its sidebar links disappear and its routes 404.
            Anything not listed in <span class="font-mono text-xs">Features::ALL</span> (Dashboard, Settings
            itself) is core and always on.
        </p>

        <p>
            State lives in the <span class="font-mono text-xs">features</span> table
            (<span class="font-mono text-xs">key</span>, <span class="font-mono text-xs">label</span>,
            <span class="font-mono text-xs">is_enabled</span>), read through
            <span class="font-mono text-xs">Feature::enabledMapCached()</span> &mdash; a forever cache of a
            plain key&rarr;bool array, busted by the model's <span class="font-mono text-xs">saved</span> and
            <span class="font-mono text-xs">deleted</span> hooks. A key with no row yet reads as enabled, so a
            feature added to the list after the table was last seeded still shows checked.
        </p>

        <x-admin-doc-code label="Reading a feature anywhere in the app" file="app/Support/Features.php:63">
use App\Support\Features;

if (Features::enabled('comments')) {
    // ...
}

// Gating a route group - routes/admin.php
Route::middleware('feature:pages')->group(function () { ... });

// Gating the API - routes/api/v1.php
Route::middleware('feature:vouchers')->prefix('vouchers')->group(function () { ... });
        </x-admin-doc-code>

        <p>
            Three mechanisms have to agree or a link survives a toggle and opens a dead end:
        </p>
        <ol class="list-decimal space-y-1.5 pl-5 marker:text-zinc-400">
            <li>The route group is wrapped in <span class="font-mono text-xs">feature:{key}</span> middleware.</li>
            <li>
                <span class="font-mono text-xs">MenuItem::FEATURE_ROUTE_PREFIXES</span> maps the route-name
                prefix to the feature key, so <span class="font-mono text-xs">isVisibleToCurrentUser()</span>
                hides the sidebar link.
            </li>
            <li>A group left with no visible children is dropped entirely.</li>
        </ol>

        <p>
            Settings and header widgets are gated by the same idea, one level down: a toggle for a module that
            is no longer there is a dead control, and the header widget it enables has nothing left to do.
            <span class="font-mono text-xs">Features::SETTING_FEATURES</span> maps such a setting key to the
            feature it belongs to, and <span class="font-mono text-xs">Features::settingAvailable()</span> is
            the single test both sides ask &mdash; the Settings card and the widget in
            <span class="font-mono text-xs">layouts/admin.blade.php</span> &mdash; so they cannot drift apart.
            Hiding a toggle hides only the control; the stored value is left alone and comes back intact if the
            feature is turned on again.
        </p>

        <x-admin-doc-code label="A feature-owned setting and the widget behind it" file="app/Support/Features.php:56">
public const SETTING_FEATURES = [
    'shop_toggle_enabled' => 'products',
    'additional_data_products_enabled' => 'products',
    'additional_data_posts_enabled' => 'blog',
    'language_switcher_enabled' => 'localization',
];

// The Settings card, and the admin header widget it enables
@if (Features::settingAvailable('shop_toggle_enabled'))
    ...
@endif
        </x-admin-doc-code>

        <p class="text-xs text-zinc-500">
            The same screen also has to be hidden for the people who cannot use it: the Features page itself
            is developer-environment + admin-role only (see
            <span class="font-mono">Admin\Features\Index::mount()</span>), and
            <span class="font-mono">MenuItem::isVisibleToCurrentUser()</span> applies the same test so the
            link is not advertised to anyone it would 404 for.
        </p>

        <x-admin-doc-code label="Adding a feature" file="">
1. Add the key and label to Features::ALL
2. Wrap the route group in middleware('feature:{key}')
3. Add the route prefix -> key pair to MenuItem::FEATURE_ROUTE_PREFIXES
4. Map any setting/header widget that belongs to it in Features::SETTING_FEATURES
5. The toggle appears on the Features screen automatically
        </x-admin-doc-code>

        <div>
            <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Feature toggles ({{ count(Features::ALL) }})
            </flux:text>
            <div class="grid gap-1.5 sm:grid-cols-2">
                @php $enabledMap = \App\Models\Feature::enabledMapCached(); @endphp
                @foreach (Features::ALL as $key => $label)
                    <div class="flex items-center gap-2 rounded-md border border-zinc-100 px-2.5 py-1.5 dark:border-zinc-800">
                        <span @class([
                            'size-1.5 shrink-0 rounded-full',
                            'bg-emerald-500' => $enabledMap->get($key, true),
                            'bg-zinc-300 dark:bg-zinc-600' => ! $enabledMap->get($key, true),
                        ])></span>
                        <span class="font-mono text-[11px] text-zinc-500">{{ $key }}</span>
                        <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-[11px] text-zinc-400">
                Green = currently on. Change them at <span class="font-mono">Admin &rarr; Features</span>.
            </p>
        </div>
    </x-admin-doc-section>

    {{-- 4. Settings --}}
    <x-admin-doc-section id="settings" icon="cog-6-tooth" title="4. Settings"
        description="app/Models/Setting.php &middot; app/Livewire/Admin/Settings/Index.php">
        <p>
            Settings are a flat key/value store (<span class="font-mono text-xs">key</span>,
            <span class="font-mono text-xs">value</span>, <span class="font-mono text-xs">type</span>,
            <span class="font-mono text-xs">group</span>, <span class="font-mono text-xs">is_public</span>) read
            everywhere through static helpers. Adding a setting needs no migration &mdash; add a row and a form
            field.
        </p>

        <x-admin-doc-code label="Reading and writing" file="app/Models/Setting.php:127">
use App\Models\Setting;

$name    = Setting::get('site_name');
$perPage = Setting::perPage();               // typed accessor, with its default baked in

Setting::set('site_theme', 'ecommerce');    // ALWAYS this, never ->update()
        </x-admin-doc-code>

        <p>
            <strong>Always write through <span class="font-mono text-xs">Setting::set()</span>.</strong> It
            upserts the row, clears the request memo, and bumps
            <span class="font-mono text-xs">settings:cache-version</span> &mdash; which orphans the cached
            settings map without needing cache tagging, so it works on any cache store. A direct
            <span class="font-mono text-xs">update()</span> leaves a stale map behind. If you must write
            outside the model (a migration or seeder), bump the version yourself or
            <span class="font-mono text-xs">Cache::forget('settings:all:v'.Setting::cacheVersion())</span>.
        </p>

        <p>
            The whole table is read once per request as one cached map
            (<span class="font-mono text-xs">Setting::allCached()</span>), not one cache entry per key,
            because with a database cache store each of those would be its own SELECT and the storefront
            reads a dozen of them per page.
        </p>

        <p>
            Booleans are stored as the strings <span class="font-mono text-xs">"1"</span> /
            <span class="font-mono text-xs">"0"</span> with no cast on the model, so a plain
            <span class="font-mono text-xs">(bool)</span> is a trap in two directions: PHP and JavaScript
            disagree about the string <span class="font-mono text-xs">"0"</span>. The Settings form casts to a
            real boolean before binding a checkbox (JS truthiness would render it checked forever), and typed
            accessors that read stored strings use a small <span class="font-mono text-xs">truthy()</span>
            helper. Follow that pattern for any new boolean setting.
        </p>

        <x-admin-doc-code label="Typed accessors worth copying" file="app/Models/Setting.php">
public static function vatEnabled(): bool          { return (bool) static::get('vat_enabled', false); }
public static function productMinStockQuantity(): int { return (int) static::get('product_min_stock_quantity', 10); }
public static function perPage(): int              { return (int) static::get('pagination_per_page', 10); }

// reads stored "1"/"0"/"true" strings
public static function postAdditionalDataEnabled(): bool { return self::truthy(static::get('...', '1')); }
        </x-admin-doc-code>

        <p>
            A handful of settings are per-language &mdash; the global SEO copy, listed in
            <span class="font-mono text-xs">Setting::TRANSLATABLE</span>. A Setting row has no model instance
            to hang a translatable cast off, so the <span class="font-mono text-xs">{code: value}</span> map is
            stored inside the same <span class="font-mono text-xs">value</span> column and decoded by
            <span class="font-mono text-xs">Setting::translated($key)</span>, which falls back to the primary
            locale so a page that only translated its title still unfurls with a usable description.
        </p>

        <p>
            The Settings screen groups rows by the <span class="font-mono text-xs">group</span> column and
            renders whatever is not hand-drawn. Some groups are deliberately excluded from the generic loop
            because they get a bespoke control instead &mdash; colours, currency/VAT, the backend typeface, the
            floating button, custom code, tracking. Adding a group to that exclusion list is what stops a row
            surfacing as a plain text input in the wrong tab.
        </p>

        <p class="text-xs text-zinc-500">
            Note that <span class="font-mono">maintenance_mode</span> is <em>not</em> a settings row &mdash;
            maintenance is Artisan <span class="font-mono">down</span>/<span class="font-mono">up</span>,
            driven from Developer Tools.
        </p>

        <x-admin-doc-code label="Which screen owns what" file="">
Admin -> Settings          general, pagination, images, localization, newsletter + DB-backed groups
                            plus hand-drawn: colours, currency/VAT, backend font, floating button
                            APP_ENV is written to .env through EnvFile::set()
Admin -> Theme Settings    site_theme + homepage copy/imagery + every theme's own settings.blade.php
Admin -> Features          the feature toggles (features table, not settings)
Admin -> Developer Tools   .env keys, maintenance mode, API settings
Admin -> SEO / Social / Payment Gateways   their own setting groups
        </x-admin-doc-code>

        {{-- The Constant card's own destination, on the Settings screen's
             "Constant" tab. Grouped rather than scattered across the section
             above because the whole feature is one idea told in one order -
             what it is, the rules a key must satisfy, how to read it, and where
             it surfaces - and a jump target halfway through that is the one
             place a reader is guaranteed to arrive. --}}
        <div id="constants" class="scroll-mt-24">
            <x-admin-doc-code label="Constants are one JSON setting, not rows" file="Admin -> Settings -> Constant tab">
[{ "key": "support_email", "type": "textarea", "value": "support@example.com" },
 { "key": "support_logo",  "type": "file",     "value": "https://shop.test/storage/media/9/logo.png" }]
            </x-admin-doc-code>

            <p>
                A <strong>constant</strong> is a freeform key/value pair you can reuse site-wide &mdash;
                phone numbers, social links, footer text &mdash; without hard-coding it into a theme template
                or a page. The whole list lives in a single
                <span class="font-mono text-xs">constants</span> setting as a JSON array of
                <span class="font-mono text-xs">{key, type, value}</span> objects, not as separate setting rows,
                which is why they never appear in the generic settings loop on the other tabs.
            </p>

            <p>
                Keys are validated as <span class="font-mono text-xs">/^[A-Za-z0-9_]*$/</span> with a
                <span class="font-mono text-xs">max:255</span> length, and must be unique
                case-insensitively &mdash; the duplicate check lowercases and trims before comparing, so
                <span class="font-mono text-xs">Support_Email</span> and
                <span class="font-mono text-xs">support_email</span> cannot both be saved. The
                <span class="font-mono text-xs">regex</span> rule is a backstop rather than the thing you feel:
                an <span class="font-mono text-xs">updated()</span> hook rewrites the field in place while you
                type, collapsing whitespace runs to a single <span class="font-mono text-xs">_</span> and
                <em>deleting</em> every other character. So a hyphen is dropped rather than converted &mdash;
                typing <span class="font-mono text-xs">support-email</span> yields
                <span class="font-mono text-xs">supportemail</span>, which saves cleanly and then reads back
                under a name you did not choose.
            </p>

            <x-admin-doc-code label="Reading a constant" file="in any theme template or page">
{{-- returns the raw string, or null when the key does not exist --}}
@@if (setting_constant('support_email'))
    &lt;a href="mailto:@{{ setting_constant('support_email') }}"&gt;Contact support&lt;/a&gt;
@@endif
            </x-admin-doc-code>

            <x-admin-doc-note tone="warning" title="A missing key returns null silently">
                <span class="font-mono text-xs">setting_constant()</span> returns
                <span class="font-mono text-xs">?string</span> and has no default, no logging and no thrown
                exception on a miss. Renaming or deleting a key therefore breaks every template that reads it
                with no signal beyond an empty string. Combined with the silent rewrite above, a key is the one
                name in the codebase that can change under you without an error &mdash; so wrap every read in a
                <span class="font-mono text-xs">@@if</span> and grep before renaming one.
            </x-admin-doc-note>

            <p>
                The two types differ only in how the value is entered. A
                <span class="font-mono text-xs">textarea</span> constant takes free text.
                A <span class="font-mono text-xs">file</span> constant renders the media picker instead, and
                the picker writes the asset's <strong>public URL</strong> straight into
                <span class="font-mono text-xs">value</span> &mdash; so the stored value already is the URL and
                nothing resolves it at read time. Replacing the file rewrites that URL, and because the value is
                validated as <span class="font-mono text-xs">string|max:1000</span> a very long signed or
                temporary URL will fail to save.
            </p>

            <x-admin-doc-code label="Where constants surface" file="GET /api/v1/settings">
{
  "data": {
    "constant": { "support_email": "support@example.com" }
  }
}
            </x-admin-doc-code>

            <p class="text-xs text-zinc-500">
                The public API returns constants as a flat <span class="font-mono text-xs">key =&gt; value</span>
                map under <span class="font-mono text-xs">data.constant</span> &mdash; the
                <span class="font-mono text-xs">{key,type,value}</span> array is the admin form's shape, not the
                API's, and <span class="font-mono text-xs">type</span> is not exposed. The decode mirrors
                <span class="font-mono text-xs">setting_constant()</span> in
                <span class="font-mono text-xs">app/Support/helpers.php</span>; the endpoint takes the same
                <span class="font-mono text-xs">?locale=</span> contract as the rest of the public API, which
                constants ignore because they are not translatable.
            </p>
        </div>

        {{-- Developer Tools & Integrations. One anchor per card on that screen,
             named integration-{card}; each is the target of that card's ⓘ button. --}}
        <div id="developer-tools" class="scroll-mt-24 space-y-6">
            <div>
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Developer Tools &amp; Integrations</p>
                <p class="mt-1">
                    The <strong>Developer Tools</strong> screen (General, Authentication, Integrations) writes to
                    <span class="font-mono text-xs">.env</span> through
                    <span class="font-mono text-xs">EnvFile</span> rather than to the settings table, because most
                    of these values feed Laravel's <span class="font-mono text-xs">config()</span> at boot &mdash;
                    so a change is only picked up after the config cache is cleared, which the save action does for
                    you. The sections below document what each value does and where it is read; the
                    credential-acquisition steps are kept to the console path per provider rather than
                    click-by-click, because provider UIs change faster than this codebase does.
                </p>
            </div>

            <div id="integration-maintenance" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Maintenance Mode (General)</p>
                <ul class="mt-2 list-disc list-inside space-y-1.5">
                    <li>Puts the public site offline for every visitor while leaving the admin panel and login reachable &mdash; so you can never lock yourself out of the switch.</li>
                    <li>It is <em>not</em> a settings row: it is Laravel's Artisan <span class="font-mono text-xs">down</span>/<span class="font-mono text-xs">up</span>, which writes <span class="font-mono text-xs">storage/framework/maintenance.php</span> and creates the <span class="font-mono text-xs">storage/framework/down</span> file. That is why it can be changed from the command line too, and why a stale <span class="font-mono text-xs">down</span> file survives a cache clear.</li>
                    <li>Enabling it asks for confirmation first; the card border turns red while it is on.</li>
                    <li>It is driven from Developer Tools, not from the Settings table &mdash; the <span class="font-mono text-xs">maintenance_mode</span> settings key does not exist.</li>
                </ul>
            </div>

            <div id="integration-debug" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Debug Mode (General)</p>
                <ul class="mt-2 list-disc list-inside space-y-1.5">
                    <li>Writes <span class="font-mono text-xs">APP_DEBUG</span> in <span class="font-mono text-xs">.env</span>. On, Laravel renders full stack traces with local variables; off, visitors get a generic error page.</li>
                    <li>Leave it off on any site real users can reach &mdash; a stack trace can expose paths, query text and (via the debug page) environment values.</li>
                    <li>It is independent of the environment name: a <span class="font-mono text-xs">local</span> install can run with debug off, and debug can be turned on in production for a few minutes while diagnosing something.</li>
                </ul>
            </div>

            <div id="integration-app" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">App (General)</p>
                <ul class="mt-2 list-disc list-inside space-y-1.5">
                    <li><strong>App Name</strong> &mdash; shown in emails, error pages and some admin screens.</li>
                    <li><strong>App URL</strong> / <strong>Frontend URL</strong> / <strong>Vendor Portal URL</strong> / <strong>Delivery Portal URL</strong> &mdash; must match the real domains this install is served on, or links, redirects and subdomain routing break. Vendor and Delivery are separate subdomains routed to <span class="font-mono text-xs">App\Livewire\Vendor\*</span> and <span class="font-mono text-xs">App\Livewire\Delivery\*</span> in <span class="font-mono text-xs">bootstrap/app.php</span>; changing the value only changes which host Laravel matches, so the DNS/hosts entry has to exist already.</li>
                    <li><strong>Cache Store</strong> &mdash; <span class="font-mono text-xs">database</span> and <span class="font-mono text-xs">file</span> work anywhere. <span class="font-mono text-xs">redis</span> is faster but only saved after the app confirms it can actually reach Redis; the save refuses it otherwise, which is why a Redis setting can silently revert.</li>
                </ul>
                <p class="mt-2 text-xs text-zinc-500">
                    The <strong>Environment</strong> value is not here &mdash; it moved to <em>Settings &rarr;
                    General</em>, where it gets its own save action and confirmation.
                </p>
            </div>

            <div id="integration-google-login" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Google Login (Authentication)</p>
                <ol class="mt-2 list-decimal list-inside space-y-1.5">
                    <li><span class="font-mono text-xs">console.cloud.google.com</span> &rarr; create or pick a project.</li>
                    <li>APIs &amp; Services &rarr; Credentials &rarr; Create Credentials &rarr; <strong>OAuth client ID</strong>, application type <strong>Web application</strong>.</li>
                    <li>Authorized redirect URIs &mdash; paste the exact <strong>Google Redirect URI</strong> shown on the form (the <span class="font-mono text-xs">${APP_URL}</span> template resolves to the current App URL).</li>
                    <li>Copy <strong>Client ID</strong> and <strong>Client Secret</strong> into the fields and save.</li>
                </ol>
                <p class="mt-2 text-xs text-zinc-500">
                    Google may require the OAuth consent screen (app name, support email) to be filled in before it
                    will issue a client ID.
                </p>
            </div>

            <div id="integration-facebook-login" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Facebook Login (Authentication)</p>
                <ol class="mt-2 list-decimal list-inside space-y-1.5">
                    <li><span class="font-mono text-xs">developers.facebook.com</span> &rarr; My Apps &rarr; Create App &rarr; type <strong>Consumer</strong> (or &ldquo;Other&rdquo;).</li>
                    <li>Add the <strong>Facebook Login</strong> product.</li>
                    <li>App Settings &rarr; Basic &mdash; copy <strong>App ID</strong> and <strong>App Secret</strong> into the fields.</li>
                    <li>Facebook Login &rarr; Settings &rarr; Valid OAuth Redirect URIs &mdash; paste the exact <strong>Facebook Redirect URI</strong> shown on the form.</li>
                    <li>Save on Facebook's side, then save here.</li>
                </ol>
                <p class="mt-2 text-xs text-zinc-500">
                    The app stays in <em>Development</em> mode by default &mdash; only you and any testers you add
                    under Roles can log in until it passes App Review.
                </p>
            </div>

            <div id="integration-recaptcha" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">reCAPTCHA (Authentication)</p>
                <ol class="mt-2 list-decimal list-inside space-y-1.5">
                    <li><span class="font-mono text-xs">google.com/recaptcha/admin</span> &rarr; create a new site.</li>
                    <li>Type <strong>reCAPTCHA v3</strong>.</li>
                    <li>Add this domain and <span class="font-mono text-xs">localhost</span> for local testing.</li>
                    <li>Copy <strong>Site Key</strong> and <strong>Secret Key</strong> into the fields and save.</li>
                </ol>
                <p class="mt-2 text-xs text-zinc-500">
                    v3 is invisible &mdash; no checkbox. It scores each login attempt while both keys are set;
                    clearing both turns it off. It guards the admin login form only.
                </p>
            </div>

            <div id="integration-turnstile" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Cloudflare Turnstile (Authentication)</p>
                <ol class="mt-2 list-decimal list-inside space-y-1.5">
                    <li><span class="font-mono text-xs">dash.cloudflare.com</span> &rarr; <strong>Turnstile</strong> (left menu) &rarr; <strong>Add widget</strong>.</li>
                    <li>Give it a name and add this site's domain under <strong>Hostname management</strong> (add <span class="font-mono text-xs">localhost</span> for local testing).</li>
                    <li>Widget mode: <strong>Managed</strong>. Click <strong>Create</strong>.</li>
                    <li>Copy the <strong>Site Key</strong> and <strong>Secret Key</strong> into Developer Tools &rarr; Env &rarr; Cloudflare Turnstile and save.</li>
                </ol>
                <p class="mt-2 text-xs text-zinc-500">
                    The widget stays hidden unless Cloudflare needs the visitor to interact. Both providers can keep
                    their keys saved; set <strong>Active Captcha</strong> to <span class="font-mono">turnstile</span> in the same Env tab
                    to use it (or <span class="font-mono">recaptcha</span> to switch back). Which logins
                    ask for it is the per-role switch on Roles. For local testing, Cloudflare publishes dummy keys:
                    site <span class="font-mono">1x00000000000000000000AA</span>, secret
                    <span class="font-mono">1x0000000000000000000000000000000AA</span> (always pass).
                </p>
            </div>

            <div id="integration-pixel" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Google Pixel (Integrations)</p>
                <ol class="mt-2 list-decimal list-inside space-y-1.5">
                    <li>GA4 &mdash; <span class="font-mono text-xs">analytics.google.com</span> &rarr; Admin &rarr; Data Streams &rarr; your web stream &rarr; copy the <strong>Measurement ID</strong> (<span class="font-mono text-xs">G-XXXXXXXXXX</span>).</li>
                    <li>Or Google Ads conversion tracking &mdash; Ads &rarr; Tools &rarr; Conversions &rarr; copy the <span class="font-mono text-xs">AW-XXXXXXXXX</span> ID.</li>
                    <li>Paste into the field and save.</li>
                </ol>
                <x-admin-doc-code label="The only value the frontend needs to read" file="GET /api/v1/settings/public">
{
  "data": {
    "tracking": { "google_pixel_id": "G-XXXXXXXXXX" }
  }
}
                </x-admin-doc-code>
                <p class="text-xs text-zinc-500">
                    It is already public &mdash; no auth, no server proxy. Fetch it once (cache it &mdash; the
                    frontend revalidates hourly) and only inject the gtag script when the ID is non-empty, so a
                    blank field means no analytics script at all rather than a broken one.
                </p>
            </div>

            <div id="integration-google-maps" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Google Maps (Integrations)</p>
                <ol class="mt-2 list-decimal list-inside space-y-1.5">
                    <li><span class="font-mono text-xs">console.cloud.google.com</span> &rarr; create or pick a project.</li>
                    <li>APIs &amp; Services &rarr; Library &rarr; enable <strong>Maps JavaScript API</strong> (and <strong>Places API</strong> if needed).</li>
                    <li>APIs &amp; Services &rarr; Credentials &rarr; Create Credentials &rarr; <strong>API Key</strong>.</li>
                    <li>Restrict the key to <strong>HTTP referrers</strong> &mdash; your domain and <span class="font-mono text-xs">localhost</span>.</li>
                    <li>Copy the key into the field and save.</li>
                </ol>
                <p class="mt-2 text-xs text-zinc-500">
                    This key runs client-side, so referrer restriction &mdash; not secrecy &mdash; is what keeps it
                    safe to expose. The card's live preview is a saved-key sanity check only, and it surfaces a
                    rejected key (wrong key, billing off, or this domain missing from the referrer list) as an
                    inline message instead of a blank box.
                </p>
            </div>

            <div id="integration-aws-s3" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">AWS S3 (Integrations)</p>
                <ol class="mt-2 list-decimal list-inside space-y-1.5">
                    <li><span class="font-mono text-xs">console.aws.amazon.com/s3</span> &rarr; create or pick a bucket; note its name and region.</li>
                    <li><span class="font-mono text-xs">console.aws.amazon.com/iam</span> &rarr; Users &rarr; create a user with programmatic access and S3 access to that bucket.</li>
                    <li>Copy <strong>Access Key ID</strong> and <strong>Secret Access Key</strong> &mdash; AWS shows the secret once.</li>
                    <li>Fill <strong>Region</strong> and <strong>Bucket</strong> to match, then save.</li>
                </ol>
                <p class="mt-2 text-xs text-zinc-500">
                    Leave <strong>Use Path-Style Endpoint</strong> off for real AWS S3; it is only for
                    S3-compatible services (MinIO, DigitalOcean Spaces) that require it. None of these are read
                    unless <span class="font-mono text-xs">FILESYSTEM_DISK</span> is set to <span class="font-mono text-xs">s3</span> &mdash; otherwise uploads stay on the local disk.
                </p>
            </div>

            <div id="integration-firebase" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Firebase (Integrations)</p>
                <ol class="mt-2 list-decimal list-inside space-y-1.5">
                    <li><span class="font-mono text-xs">console.firebase.google.com</span> &rarr; your project &rarr; gear icon &rarr; <strong>Project settings</strong>.</li>
                    <li><strong>Service accounts</strong> tab &rarr; <strong>Generate new private key</strong> &mdash; downloads a JSON file.</li>
                    <li>Upload that JSON to <span class="font-mono text-xs">storage/app/private</span> via File Manager.</li>
                    <li>Paste its path <em>relative to that folder</em> into the field (e.g. <span class="font-mono text-xs">firebase-service-account.json</span>, or <span class="font-mono text-xs">firebase/service-account.json</span> in a subfolder), then save.</li>
                    <li>The icon beside the field turns green once the file is found at that path.</li>
                </ol>
                <p class="mt-2 text-xs text-zinc-500">
                    That JSON grants full admin access to the Firebase project, so it belongs on the private disk and
                    never in version control. The green icon only asserts the file exists &mdash; it does not prove
                    the credentials are still valid.
                </p>
            </div>

            <div id="integration-cms-editor" class="scroll-mt-24">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">CMS Editor (Integrations)</p>
                <ul class="mt-2 list-disc list-inside space-y-1.5">
                    <li>The <strong>Edit</strong> buttons on Products, Pages and Posts build their Puck editor URL from this value.</li>
                    <li>It must point at the host running the Next.js CMS editor, including a non-standard port if any (e.g. <span class="font-mono text-xs">http://194.233.65.83:3002</span>).</li>
                    <li>Leave it blank to disable the visual editor buttons, or to keep them on <span class="font-mono text-xs">localhost</span> in a local install.</li>
                </ul>
                <p class="mt-2 text-xs text-zinc-500">
                    Read at runtime as <span class="font-mono text-xs">config('cms.editor_base_url')</span>; saving
                    clears the config cache so the new value is used on the next request.
                </p>
            </div>

            <x-admin-doc-note tone="warning" title="Where to get a credential is guidance, not code">
                The console paths above change as providers redesign their UIs, so treat them as a starting point
                rather than a contract. What this codebase guarantees is narrower: the field is read as the config
                key named, blank usually disables the feature, and secrets belong on the private disk or in
                <span class="font-mono text-xs">.env</span> &mdash; never in a template.
            </x-admin-doc-note>
        </div>
    </x-admin-doc-section>

    {{-- 5. Pages --}}
    <x-admin-doc-section id="pages" icon="document-text" title="5. Pages"
        description="app/Models/Page.php &middot; app/Livewire/Admin/Pages/">
        <p>
            A Page is more than a body of text. It is the single record that owns a URL, its SEO, its visual
            layout and its inner content &mdash; and it doubles as the SEO/URL record for products, posts and
            categories, which is why nearly everything else in the CMS pairs with one.
        </p>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-900/40">
                    <tr>
                        <th class="px-3 py-2 font-medium">Field</th>
                        <th class="px-3 py-2 font-medium">Meaning</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-600 dark:divide-zinc-800 dark:text-zinc-300">
                    @foreach ([
                        ['title / description / content', 'Translatable (en, bn). Title drives the slug when none is given.'],
                        ['slug', 'Unique, the public URL. Generated from the primary-locale title; never regenerated once set.'],
                        ['status', 'active | inactive. Toggling a paired page flips the entity too, so the two never disagree.'],
                        ['template', 'Which visual template the page renders through. Defaults to puck.'],
                        ['type', 'page | post | product | product_category | post_category — see below.'],
                        ['puck_data', 'JSON from the visual Page Builder (external editor).'],
                        ['constant', 'JSON list of {key, type, value} pairs exposed to templates.'],
                        ['product_id / post_id / category_id', 'Set when the page is paired with an entity. Its slug is then owned by that entity.'],
                        ['sort_order', 'Ordering in the nav and in the admin list.'],
                        ['seo_*, og_*, twitter_*', 'Per-page meta. The text ones are translatable; the images and canonical are not.'],
                        ['no_index / no_follow', 'Robots directives.'],
                        ['canonical_base / canonical_slug', 'Override the canonical URL when it differs from the slug.'],
                    ] as [$field, $meaning])
                        <tr>
                            <td class="px-3 py-2 font-mono text-[11px] text-zinc-700 dark:text-zinc-200">{{ $field }}</td>
                            <td class="px-3 py-2">{{ $meaning }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p>
            The <span class="font-mono text-xs">type</span> column is the important one. A
            <span class="font-mono text-xs">page</span> is standalone &mdash; About, Contact, FAQ. Every other
            type is a <strong>paired</strong> page: the record holds the URL and SEO for a product, post or
            category, and the entity itself holds the content. That is why
            <span class="font-mono text-xs">Post::slug</span> is a virtual accessor reading its page, and why
            deleting a category has to cascade through
            <span class="font-mono text-xs">PageCascade</span> to take its page with it.
        </p>

        <x-admin-doc-code label="Pairing rules" file="app/Livewire/Admin/Pages/Form.php:209">
// A linked page's slug belongs to the entity, so it is re-read from the DB
// on save rather than trusted from the submitted form:
if ($this->isLinked()) {
    $slug = $linked->fresh()->slug;
}

// Status is kept in sync both ways, from either screen:
$page->update(['status' => $new]);   // Pages\Index::toggleStatus()
// $entity->update(['status' => $new]);
        </x-admin-doc-code>

        <p>
            Revisions are automatic. When a page's <span class="font-mono text-xs">content</span> changes and
            the previous content was not empty and someone is logged in, the model snapshots the
            <em>original</em> content into <span class="font-mono text-xs">page_revisions</span> before the
            write &mdash; so the revision list is always the history of what was there, not what is there now.
        </p>

        <p>
            New pages are created <span class="font-mono text-xs">inactive</span> on purpose: the form saves the
            record, then the list screen is where status is switched. A half-built page therefore never becomes
            reachable by typing its URL.
        </p>

        <x-admin-doc-code label="Rendering a page on the storefront" file="app/Http/Controllers/Themes/Concerns/RendersStandalonePage.php">
// Each theme registers its own route(s) for standalone pages, and each has its
// own PageController using this trait:
$page = Page::where('slug', $slug)
    ->where('type', 'page')
    ->where('status', 'active')
    ->firstOrFail();

return $this->view('page', [
    'page'       => $page,
    'sections'   => CmsSection::cachedForPage($page->id),
    'title'      => $page->seo_title ?: $page->getTranslation('title', 'en', false),
    'currentSlug' => $slug,
]);
        </x-admin-doc-code>

        <p class="text-xs text-zinc-500">
            Standalone pages are whitelisted per theme rather than served by a bare
            <span class="font-mono">/{slug}</span> wildcard &mdash; the default theme registers
            <span class="font-mono">about|contact|faq</span> &mdash; so a page route can never shadow an auth
            or system route regardless of registration order. A theme with no
            <span class="font-mono">page.blade.php</span> registers no page route at all, so it simply has no
            standalone pages.
        </p>

        <x-admin-doc-code label="The Page Builder (Puck)" file="app/Support/PuckEditor.php">
// Laravel mints a short-lived Sanctum token; the external Puck app
// (CMS_EDITOR_BASE_URL, default http://localhost:3000) authenticates with it
// and reads/writes this page's puck_data over the admin API.

$token = PuckEditor::token($user, "puck-builder-{$page->id}");   // scoped per page
// open: {CMS_EDITOR_BASE_URL}/puck/edit/{$page->type}/{$pageId}#token={$token}

// Lifetime: config('cms.puck_session_minutes') <- PUCK_SESSION in .env
// URL:      config('cms.editor_base_url')      <- CMS_EDITOR_BASE_URL in .env
// Both editable from the Settings button on the admin Pages screen.
        </x-admin-doc-code>

        <p>
            The token name is scoped per page on purpose: a single shared name meant opening the editor for page
            B silently invalidated an open tab for page A, breaking that tab's next save with a 401. Scoping
            keeps re-opening the <em>same</em> page idempotent without touching any other page's session.
        </p>
    </x-admin-doc-section>

    {{-- 6. Page content --}}
    <x-admin-doc-section id="page-content" icon="squares-2x2" title="6. What a page contains"
        description="CmsSection (the cms table) &middot; Page constants &middot; PageBlocks">
        <p>
            There are three ways content gets inside a page, and they answer different questions.
        </p>

        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ([
                ['squares-2x2', 'Visual layout', 'puck_data from the Page Builder. Freeform composition of blocks, owned by the editor app.'],
                ['rectangle-group', 'Sections', 'CmsSection rows: repeatable cards (image/title/description) plus key/value constants. The workhorse.'],
                ['key', 'Constants', 'One key/value list on the page itself. For single values, not lists.'],
            ] as [$icon, $label, $body])
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center gap-2">
                        <x-dynamic-component :component="'flux::icon.'.$icon" class="size-4 text-primary" />
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $label }}</p>
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $body }}</p>
                </div>
            @endforeach
        </div>

        <div class="pt-1">
            <p class="font-semibold text-zinc-800 dark:text-zinc-100">CMS sections</p>
            <p class="mt-1">
                A section is a named block inside one page: a repeatable list of cards, plus a list of
                key/value pairs. Reached from that page's row on the Pages screen, never standalone &mdash; a
                section has no meaning without the page it belongs to. The name is unique per page and is what
                a template anchors to, so a theme can target one section without rendering the rest.
            </p>
        </div>

        <x-admin-doc-code label="Reading sections in a theme template" file="themes/default/page.blade.php">
@verbatim
@foreach ($sections as $section)
    @continue(blank($section->localizedCards()))   {{-- skip sections with no cards #}}

    &lt;section id="{{ $section->name }}"&gt;
        @foreach ($section->localizedCards() as $card)
            &lt;img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"&gt;
            &lt;h3&gt;{{ $card['title'] }}&lt;/h3&gt;
            &lt;p&gt;{{ $card['description'] }}&lt;/p&gt;
        @endforeach
    &lt;/section&gt;
@endforeach

{{-- and the constants of one section, as a plain lookup map --}}
@php $constants = $section->constantMap(); @endphp
{{ $constants['cta_label'] ?? '' }}
@endverbatim
        </x-admin-doc-code>

        <p>
            <span class="font-mono text-xs">localizedCards()</span> projects each card down to exactly
            image/title/description, so a stray column in the stored JSON cannot leak into a template.
            <span class="font-mono text-xs">constantMap()</span> collapses the stored
            <span class="font-mono text-xs">[{key, value}]</span> list into a
            <span class="font-mono text-xs">key =&gt; value</span> map, skipping blank keys &mdash; both
            shapes exist because the admin form has to be able to reorder and remove rows like any repeater,
            while consumers just want to look a value up.
        </p>

        <p>
            Sections are cached per page, forever, keyed by a version that
            <span class="font-mono text-xs">CmsSection::flushCache()</span> bumps on every write &mdash; the
            same cache the public CMS API reads, so one invalidation covers both. The cache stores raw attribute
            arrays and re-hydrates on read: caching Eloquent objects or a
            <span class="font-mono text-xs">toArray()</span> result both break, the latter because it
            double-decodes the JSON casts on the way back in.
        </p>

        <p class="text-xs text-zinc-500">
            One naming trap: the scope is <span class="font-mono">ofPage()</span>, not
            <span class="font-mono">forPage()</span> &mdash; Eloquent's query builder already has a real
            <span class="font-mono">forPage()</span> pagination helper, and a scope with that name silently
            hijacks every <span class="font-mono">paginate()</span> call.
        </p>

        <div class="pt-1">
            <p class="font-semibold text-zinc-800 dark:text-zinc-100">Constants</p>
            <p class="mt-1">
                A page (and a section, and the settings screen) can each carry key/value constants &mdash;
                typically a text block or a file path. Keys are sanitized to
                <span class="font-mono text-xs">/^[A-Za-z0-9_]*$/</span> as they are typed, must be unique
                case-insensitively, and the value type is <span class="font-mono text-xs">textarea</span> or
                <span class="font-mono text-xs">file</span>. Older rows saved with the since-removed
                <span class="font-mono text-xs">text</span> type are folded into
                <span class="font-mono text-xs">textarea</span> on load so they still render and edit.
            </p>
        </div>

        {{-- ── The three constant/cards help buttons on the Pages and CMS screens.
             Anchored separately because each is a different scope and a different
             arity, and a reader following one button needs only its own answer —
             but they share the key rules above, the storage shape, and the same
             silent-rewrite trap, so those are referenced rather than restated. --}}
        <div id="page-constants" class="scroll-mt-24">
            <div class="pt-1">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Page constants, read from a template</p>
                <p class="mt-1">
                    The <span class="font-mono text-xs">Constants</span> card on a page's own edit screen stores
                    its list on the Page row, so it is scoped to that one page and travels with the page when it is
                    duplicated or translated &mdash; unlike a site-wide constant, which is one shared list.
                </p>
            </div>

            <x-admin-doc-code label="Reading a page constant" file="in any theme template or view">
{{-- the page's slug is 'about' --}}
@@if (page_constant('about', 'og_type') === 'website')
    &lt;meta property="og:type" content="website"&gt;
@@endif
            </x-admin-doc-code>

            <p>
                The argument order is <span class="font-mono text-xs">page_constant($slug, $key)</span> &mdash;
                slug first, then key &mdash; and it returns <span class="font-mono text-xs">?string</span>, so
                it resolves through <span class="font-mono text-xs">Page::constantMap()</span> and is
                <span class="font-mono text-xs">null</span> for a key that was never set. Same silent-miss
                behaviour as <span class="font-mono text-xs">setting_constant()</span> and for the same reason:
                rename the key and every template reading it renders empty with nothing in the log. The key is
                also rewritten in place as you type, so grep the templates before renaming one.
            </p>

            <p class="text-xs text-zinc-500">
                On the public API this list is
                <span class="font-mono text-xs">data.constant</span> on the page resource &mdash; a flat
                <span class="font-mono text-xs">key =&gt; value</span> map from
                <span class="font-mono text-xs">Page::constantMap()</span>, not the stored
                <span class="font-mono text-xs">[{key, value}]</span> list. Page constants are not
                translatable, so the resource ignores <span class="font-mono text-xs">?locale=</span> for them.
            </p>
        </div>

        <div id="section-constants" class="scroll-mt-24">
            <div class="pt-1">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Section constants, read from a template</p>
                <p class="mt-1">
                    A CMS section carries its own constant list, so the same key can mean different things in two
                    sections of one page &mdash; a <span class="font-mono text-xs">heading</span> per section
                    without the sections colliding. Reach it by page slug, section name, then key.
                </p>
            </div>

            <x-admin-doc-code label="Reading a section constant" file="in any theme template">
{{-- page slug 'home', section name 'cta', key 'heading' --}}
@@php($ctaHeading = cms_constant('home', 'cta', 'heading'))
@@if ($ctaHeading)&lt;h2&gt;@{{ $ctaHeading }}&lt;/h2&gt;@@endif
            </x-admin-doc-code>

            <p>
                <span class="font-mono text-xs">cms_constant($slug, $name, $key)</span> is three levels deep and
                returns <span class="font-mono text-xs">?string</span>. It looks the page up by slug, then reads
                the section from <span class="font-mono text-xs">CmsSection::cachedForPage()</span> by
                <span class="font-mono text-xs">name</span> &mdash; the name, not the section's id, which is why a
                renamed section silently returns <span class="font-mono text-xs">null</span> in every template
                that referenced it. A wrong page slug short-circuits to
                <span class="font-mono text-xs">null</span> before touching the section cache at all.
            </p>

            <p class="text-xs text-zinc-500">
                On the API it is <span class="font-mono text-xs">data.cms[].constant</span>, the same flat map.
                Note the difference from a page constant's
                <span class="font-mono text-xs">data.constant</span>: one hangs off the page resource, the other
                off each entry inside the page's <span class="font-mono text-xs">cms</span> array.
            </p>
        </div>

        <div id="section-cards" class="scroll-mt-24">
            <div class="pt-1">
                <p class="font-semibold text-zinc-800 dark:text-zinc-100">Section cards, read from a template</p>
                <p class="mt-1">
                    The <span class="font-mono text-xs">Cards</span> repeater is the most-used part of a section:
                    an ordered list of image/title/description tiles. Give the section a name, add rows under it,
                    and a template renders the whole list from that one name.
                </p>
            </div>

            <x-admin-doc-code label="Reading a section's cards" file="in any theme template">
{{-- page slug 'home', section name 'features' --}}
@@foreach (cms_cards('home', 'features') as $card)
    &lt;img src="@{{ $card['image'] }}" alt="@{{ $card['title'] }}"&gt;
    &lt;h3&gt;@{{ $card['title'] }}&lt;/h3&gt;
    &lt;p&gt;@{{ $card['description'] }}&lt;/p&gt;
@@endforeach
            </x-admin-doc-code>

            <p>
                <span class="font-mono text-xs">cms_cards($slug, $name)</span> returns an array (empty on a
                missing page or section, so a
                <span class="font-mono text-xs">@@foreach</span> over it is always safe) of rows already run
                through <span class="font-mono text-xs">localizedCards()</span>, which projects each one down to
                exactly image/title/description &mdash; so a stray key in the stored JSON cannot leak into a
                template. Every field is nullable: a card with a blank title renders a blank
                <span class="font-mono text-xs">&lt;h3&gt;</span> rather than disappearing, which is why a
                template should guard the section rather than trust it to be populated.
            </p>

            <x-admin-doc-note tone="warning" title="Cards keep their order, and there is no drag handle">
                Rows render in stored order and the admin offers no reordering &mdash; the component has no move
                or sort method, and the repeater collapses rows rather than reordering them. Changing a card's
                position means deleting and re-adding it, so insert a new row where you want it rather than
                assuming you can drag an existing one. The same caveat applies to a constant list's rows.
            </x-admin-doc-note>

            <p class="text-xs text-zinc-500">
                On the API the same list is
                <span class="font-mono text-xs">data.cms[].cards</span>, projected inline rather than through
                <span class="font-mono text-xs">localizedCards()</span> so a
                <span class="font-mono text-xs">?locale=</span> request still returns translated text.
            </p>
        </div>

        <div class="pt-1">
            <p class="font-semibold text-zinc-800 dark:text-zinc-100">Page blocks</p>
            <p class="mt-1">
                For the few widgets a section cannot express &mdash; a contact form, a booking widget &mdash;
                there is a small registry mapping a page slug to a Livewire component. Themes render whatever
                is registered for the current slug, so adding one is a one-line change rather than an edit to
                every theme's <span class="font-mono text-xs">page.blade.php</span>.
            </p>
        </div>

        <x-admin-doc-code label="Registering a page block" file="app/Support/PageBlocks.php">
public const ALL = [
    'contact' => 'frontend.contact-form',
];

    public static function for(string $slug): ?string
{
    return self::ALL[$slug] ?? null;
}
        </x-admin-doc-code>
    </x-admin-doc-section>

    {{-- 7. CMS content --}}
    <x-admin-doc-section id="cms-content" icon="tag" title="7. CMS content"
        description="Posts &middot; shared taxonomy &middot; what pairs with a Page and what does not">
        <p>
            Everything below the page layer follows the same shape: the entity owns the content, a paired Page
            owns the URL and the SEO.
        </p>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-900/40">
                    <tr>
                        <th class="px-3 py-2 font-medium">Model</th>
                        <th class="px-3 py-2 font-medium">Table</th>
                        <th class="px-3 py-2 font-medium">Paired Page?</th>
                        <th class="px-3 py-2 font-medium">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-600 dark:divide-zinc-800 dark:text-zinc-300">
                    @foreach ([
                        ['Post', 'posts', 'yes (type: post)', 'Translatable title/content/description, soft deletes, reading_time, views, published_at. slug is a virtual accessor from its page.'],
                        ['ProductCategory', 'categories', 'yes (type: product_category)', 'kind=category, type_id locked to the product pool. Tree via parent_id, with page() for the slug.'],
                        ['PostCategory', 'categories', 'yes (type: post_category)', 'Same table, kind=category, type_id locked to the post pool.'],
                        ['Tag', 'categories', 'no', 'kind=tag. Slug computed from the primary-locale name. Morphed to posts/products through taggables.'],
                        ['CmsSection', 'cms', 'belongs to one', 'Page-scoped sections. See section 6.'],
                        ['MediaLibrary', 'media_library', 'no', 'Assets. See section 9.'],
                    ] as [$model, $table, $paired, $notes])
                        <tr>
                            <td class="px-3 py-2 font-mono text-[11px] text-zinc-700 dark:text-zinc-200">{{ $model }}</td>
                            <td class="px-3 py-2 font-mono text-[11px] text-zinc-500">{{ $table }}</td>
                            <td class="px-3 py-2">{{ $paired }}</td>
                            <td class="px-3 py-2">{{ $notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p>
            The taxonomy deserves the note: <strong>categories and tags share one
            <span class="font-mono text-xs">categories</span> table</strong>, discriminated by two columns
            &mdash; <span class="font-mono text-xs">kind</span> (category / tag / brand) and
            <span class="font-mono text-xs">type_id</span> (which content pool it belongs to). One Categories
            screen and one Tags screen therefore serve both Blog and Products, which is why Products and Blog
            can share a taxonomy without duplicating it. Tags are the exception to the page pairing: they carry
            no Page at all, because a tag has no page of its own &mdash; it is a filter, not a destination.
        </p>

        <x-admin-doc-code label="Keeping a paired Page in step" file="">
// Creating or editing a post writes its Page too (slug + SEO) in the same action.
// Deleting one cascades the other way:
PageCascade::deleteEntityFor($page);   // entity + its page, in the right order

// A paired entity's slug is authoritative. The Page form re-reads it from the
// DB rather than trusting the submitted field, so a stale tab can't rewrite it.
        </x-admin-doc-code>

        <p>
            Content that appears on the storefront more than once is cached through the
            <span class="font-mono text-xs">CachesContent</span> trait, and cache invalidation is hooked to the
            model's own <span class="font-mono text-xs">saved</span> /
            <span class="font-mono text-xs">deleted</span> events rather than left to the caller. A mass update
            through the query builder bypasses those events &mdash; that is why
            <span class="font-mono text-xs">Cms\Index::reorder()</span> calls
            <span class="font-mono text-xs">flushCache()</span> explicitly.
        </p>
    </x-admin-doc-section>

    {{-- 8. Menus --}}
    <x-admin-doc-section id="menus" icon="bars-3" title="8. Menus"
        description="app/Models/MenuItem.php &middot; the menus and menu_items tables &middot; /admin/menu">
        <p>
            Menus are rows in <span class="font-mono text-xs">menu_items</span>, grouped by the
            <span class="font-mono text-xs">group</span> column, which references a menu's slug. The admin
            sidebar is the reserved group <span class="font-mono text-xs">admin-sidebar</span>; other groups
            (a frontend header nav, a footer nav, the portfolio nav) share the same table and are managed the
            same way. Items nest through <span class="font-mono text-xs">parent_id</span>, so a group is just
            an item with <span class="font-mono text-xs">is_group</span> set.
        </p>

        <x-admin-doc-code label="The columns that matter" file="database/migrations/…_create_menu_items_table.php" lang="text">
group            which menu this row belongs to (a slug, not a FK)
parent_id        nests the row under another; null = top level
is_group         renders as a collapsible heading rather than a link
label            the visible text
icon             a real Flux icon name, validated against the filesystem
route_name       a named route - preferred: it is what the visibility rules key on
url              a plain URL, for anything outside the router
sort_order       ordering within its parent (drag to reorder in the admin)
is_active        soft-hides the row without deleting it
is_short_menu    also appears in the top bar's quick-access dropdown
        </x-admin-doc-code>

        <p>
            A row with both a <span class="font-mono text-xs">route_name</span> and a
            <span class="font-mono text-xs">url</span> is legal but a trap: the visibility rules work off the
            route name, so a link stored as a bare URL skips every gate and every feature check. Prefer the
            route name.
        </p>

        <p>
            Rendering filters in three passes, and the order matters. First the tree is built and cached
            (<span class="font-mono text-xs">menuCached()</span>), then it is filtered per request by
            <span class="font-mono text-xs">isVisibleToCurrentUser()</span> &mdash; because the cache is shared
            across every user regardless of tier &mdash; then empty groups are dropped, and finally
            <span class="font-mono text-xs">Plugins::extendMenu()</span> appends the runtime Plugins dropdown.
        </p>

        <p>One row can be hidden by four independent checks:</p>
        <ul class="list-disc space-y-1.5 pl-5 marker:text-zinc-400">
            <li>
                <span class="font-mono text-xs">SYSTEM_ROUTE_PREFIXES</span> &mdash; needs the
                <span class="font-mono text-xs">access-admin-system</span> gate, so Staff never sees a link
                that would 403.
            </li>
            <li>
                <span class="font-mono text-xs">FEATURE_ROUTE_PREFIXES</span> &mdash; needs
                <span class="font-mono text-xs">Features::enabled($key)</span>.
            </li>
            <li>
                <span class="font-mono text-xs">ROLE_ROUTE_PREFIXES</span> &mdash; needs the role to be active,
                so a deactivated role takes its section with it.
            </li>
            <li>
                On the storefront, <span class="font-mono text-xs">isRenderableByCurrentTheme()</span> &mdash;
                a link to a page the active theme ships no template for is dropped rather than left as a 404.
            </li>
        </ul>

        <x-admin-doc-code label="Adding a sidebar item - the seeder" file="database/seeders/AdminMenuSeeder.php">
// The seeder deletes and rebuilds the whole group, so order is explicit.
// A top-level row:
$this->standalone('Reports', 'chart-bar', 'admin.reports', 2);

// A row inside a group. Children are ordered by their position in the
// array, not by a sort_order you pass, so "after Developer Tools" is
// simply the next line:
$this->group('Library & System', 15, [
    ['Theme Settings', 'swatch', 'admin.theme-settings'],
    ['Plugin Settings', 'squares-plus', 'admin.plugin-settings'],
    ['Developer Tools', 'command-line', 'admin.env'],
    ['Developer Guide', 'book-open', 'admin.developer-guide'],
    ['Global SEO', 'magnifying-glass', 'admin.seo'],
]);

// then: php artisan db:seed --class=AdminMenuSeeder
        </x-admin-doc-code>

<x-admin-doc-code label="Adding a sidebar item - by hand" file="">
// Admin -&gt; Menu, pick the group, create the item, set sort_order.
// Or directly, for a top-level row:
MenuItem::create([
    'group'      =&gt; MenuItem::GROUP_ADMIN_SIDEBAR,
    'label'      =&gt; 'Developer Guide',
    'icon'       =&gt; 'book-open',
    'route_name' =&gt; 'admin.developer-guide',
    'sort_order' =&gt; 26,
]);

// A row inside a group needs parent_id, and its sort_order counts from 1
// within that parent alone:
MenuItem::create([
    'group'      =&gt; MenuItem::GROUP_ADMIN_SIDEBAR,
    'parent_id'  =&gt; $libraryAndSystem-&gt;id,
    'label'      =&gt; 'Developer Guide',
    'icon'       =&gt; 'book-open',
    'route_name' =&gt; 'admin.developer-guide',
    'sort_order' =&gt; 5,
]);

// Top-level sort_order is global, so "the last item in the sidebar" just
// means the highest number in it - About holds it.
        </x-admin-doc-code>
    </x-admin-doc-section>

    {{-- 9. Media --}}
    <x-admin-doc-section id="media" icon="photo" title="9. Media Library"
        description="app/Models/MediaLibrary.php &middot; the media_library table">
        <p>
            A custom media library (not Spatie). Every asset gets a UUID on create, is stored on a named disk,
            and carries the metadata an editor needs &mdash; alt text, title, caption, description &mdash; rather
            than just a filename.
        </p>

        <p>
            One thing worth knowing: <span class="font-mono text-xs">url</span> is an accessor that
            <strong>rebuilds the URL from the disk every read</strong> and only falls back to the stored value
            if the path is gone. A URL baked in at upload time goes stale the moment
            <span class="font-mono text-xs">APP_URL</span> changes &mdash; a staging URL shipped to production,
            or a domain change &mdash; so it is recomputed rather than trusted.
        </p>

        <x-admin-doc-code label="Using media" file="">
$media = MediaLibrary::first();        // custom, not Spatie's Media model

$media->url;                          // rebuilt from the disk every read
$media->isImage();                    // also isDocument(), isVideo()
$media->dimensions;                   // width/height, read from the metadata column
$media->formatted_size;               // human-readable

// In a form:
@livewire('admin.media-library.picker-modal', ['key' => 'pages-form-picker-modal'])
        </x-admin-doc-code>

        <p>
            The picker modal is the shared selection component every form uses; passing a distinct
            <span class="font-mono text-xs">key</span> is what keeps two pickers on one page from colliding.
            Watermark settings are configured from the Media Library screen and are deliberately skipped by
            the Settings screen's save loop, so the two never write over each other.
        </p>
    </x-admin-doc-section>

    {{-- 10. Moderation --}}
    <x-admin-doc-section id="moderation" icon="chat-bubble-left-right" title="10. Comments & Reviews"
        description="Polymorphic, on posts, products and services">
        <p>
            Both are polymorphic (<span class="font-mono text-xs">commentable_*</span> /
            <span class="font-mono text-xs">reviewable_*</span>) and attach through the
            <span class="font-mono text-xs">HasComments</span> /
            <span class="font-mono text-xs">HasReviews</span> traits, so any model can opt in. Both are
            tri-state &mdash; <span class="font-mono text-xs">pending</span>,
            <span class="font-mono text-xs">approved</span>,
            <span class="font-mono text-xs">rejected</span> &mdash; and reading goes through
            <span class="font-mono text-xs">approvedComments()</span> /
            <span class="font-mono text-xs">approvedReviews()</span>, which filter at the query rather than in
            PHP.
        </p>

        <ul class="list-disc space-y-1.5 pl-5 marker:text-zinc-400">
            <li>
                Comments nest one level through <span class="font-mono text-xs">parent_id</span>; the
                <span class="font-mono text-xs">topLevel()</span> scope plus a controller check is what keeps
                replies from nesting further. Deleting a parent cascades its replies.
            </li>
            <li>
                Reviews carry a rating, and <span class="font-mono text-xs">averageRating()</span> returns it
                rounded to one decimal place or null.
            </li>
            <li>
                Both admin screens filter by status, type and (for reviews) rating, and both write to the audit
                log.
            </li>
            <li>Both public endpoints sit behind their own feature toggles.</li>
        </ul>
    </x-admin-doc-section>

    {{-- 11. API --}}
    <x-admin-doc-section id="api" icon="globe-alt" title="11. REST API"
        description="/api/v1/ &middot; routes/api/v1.php">
        <p>
            Two tiers, both under the <span class="font-mono text-xs">/api/v1</span> prefix:
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Public, read-only</p>
                <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                    No auth. Posts, pages, products, categories, services, CMS sections, and the public
                    settings/layout the app shell needs. List endpoints return a
                    <span class="font-mono">{data, meta}</span> envelope with
                    <span class="font-mono">?per_page=</span>; a single resource returns just
                    <span class="font-mono">{data: {...}}</span>.
                </p>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Admin, CRUD</p>
                <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                    <span class="font-mono">auth:sanctum</span> +
                    <span class="font-mono">can:access-admin</span>. Users and Settings sit behind a second,
                    stricter <span class="font-mono">can:access-admin-system</span> to mirror the Livewire side
                    &mdash; Staff passes the outer gate but not that one.
                </p>
            </div>
        </div>

        <p>
            Public endpoints accept <span class="font-mono text-xs">?locale=en|bn</span> for translated fields,
            falling back to the primary locale so a partly-translated record still renders. Admin Puck editing
            authenticates with the short-lived Sanctum token from section 5.
        </p>

        <x-admin-doc-code label="Reading CMS sections over the API" file="GET /api/v1/cms?page=about&name=hero" lang="text">
// ?page=<slug>            every active section on that page
// &name=<section>         just that one, 404 if it is not there

{
  "data": {
    "id": 1,
    "page_id": 4,
    "name": "hero",
    "cards": [{"image": "...", "title": "...", "description": "..."}],
    "constant": {"cta_label": "Get in touch"}
  }
}
        </x-admin-doc-code>
    </x-admin-doc-section>

    {{-- 12. Reference --}}
    <x-admin-doc-section id="reference" title="12. Reference tables" icon="table-cells">
        <div>
            <p class="mb-2 font-semibold text-zinc-800 dark:text-zinc-100">Where things live</p>
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-xs">
                    <tbody class="divide-y divide-zinc-100 text-zinc-600 dark:divide-zinc-800 dark:text-zinc-300">
                        @foreach ([
                            ['Plugins', 'app/Support/Plugins.php', 'Plugins::all() / active() / setActive()'],
                            ['Plugin wiring', 'app/Providers/PluginServiceProvider.php', 'views, migrations, routes'],
                            ['Themes', 'app/Support/Themes.php', 'all() / active() / view() / viewOrFail()'],
                            ['Theme settings', 'app/Support/ThemeSettings.php', 'text() / rows() / merge()'],
                            ['Features', 'app/Support/Features.php', 'Features::ALL / enabled()'],
                            ['Settings', 'app/Models/Setting.php', 'get() / set() / translated()'],
                            ['Page', 'app/Models/Page.php', 'published() / ofType() / constantMap()'],
                            ['Page sections', 'app/Models/CmsSection.php', 'cachedForPage() / constantMap()'],
                            ['Menus', 'app/Models/MenuItem.php', 'menuForCurrentUser() / shortMenuCached()'],
                            ['Media', 'app/Models/MediaLibrary.php', 'url / isImage() / dimensions'],
                            ['Puck tokens', 'app/Support/PuckEditor.php', 'token() / sessionMinutes()'],
                            ['Page blocks', 'app/Support/PageBlocks.php', 'ALL / for()'],
                            ['Storefront base', 'app/Http/Controllers/Themes/ThemeController.php', 'view() / viewData()'],
                            ['Shared payloads', 'app/Support/Frontend.php', 'navPages() / menuItems()'],
                        ] as [$what, $where, $api])
                            <tr>
                                <td class="px-3 py-1.5 font-medium text-zinc-700 dark:text-zinc-200">{{ $what }}</td>
                                <td class="px-3 py-1.5 font-mono text-[11px]">{{ $where }}</td>
                                <td class="px-3 py-1.5 font-mono text-[11px] text-zinc-500">{{ $api }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <p class="mb-2 font-semibold text-zinc-800 dark:text-zinc-100">Caching, and how it is invalidated</p>
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-xs">
                    <tbody class="divide-y divide-zinc-100 text-zinc-600 dark:divide-zinc-800 dark:text-zinc-300">
                        @foreach ([
                            ['settings:all:v{N}', 'forever', 'Setting::set() bumps the version (N). A direct ->update() does not.'],
                            ['cms:v{N}:page:{id}:sections', 'forever', 'CmsSection::flushCache() on every write.'],
                            ['admin-menu:items', 'forever', 'MenuItem saved/deleted hooks; drop empty groups after filtering.'],
                            ['admin-menu:short-items', 'forever', 'same as above.'],
                            ['features:enabled-map', 'forever', 'Feature saved/deleted hooks.'],
                            ['themes:all', '24 hours', 'filesystem scan; the admin picker reads it live.'],
                            ['theme.json', 'not cached', 're-read when the file mtime/size changes, so a hand edit applies immediately.'],
                        ] as [$key, $ttl, $bust])
                            <tr>
                                <td class="px-3 py-1.5 font-mono text-[11px] text-zinc-700 dark:text-zinc-200">{{ $key }}</td>
                                <td class="px-3 py-1.5">{{ $ttl }}</td>
                                <td class="px-3 py-1.5 text-zinc-500">{{ $bust }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <x-admin-doc-note tone="warning" title="Three rules that break things when forgotten">
            <ol class="list-decimal space-y-1 pl-4">
                <li>Write settings with <span class="font-mono">Setting::set()</span>, never <span class="font-mono">update()</span>.</li>
                <li>Cache is busted by model events, so a query-builder mass update needs an explicit flush.</li>
                <li>A new sidebar row wants a <span class="font-mono">route_name</span>, not a bare URL &mdash; every visibility rule keys on the route name.</li>
            </ol>
        </x-admin-doc-note>
    </x-admin-doc-section>
    </div>
</div>
