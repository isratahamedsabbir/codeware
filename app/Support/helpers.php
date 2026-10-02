<?php

use App\Models\CmsSection;
use App\Models\Page;
use App\Models\Setting;
use App\Support\Themes;
use App\Support\ThemeSettings;

if (! function_exists('display_timezone')) {
    /**
     * Timezone used to display dates to users. Dates are always stored and
     * computed internally in UTC (config('app.timezone')) — this only controls
     * how they're rendered, and is set via the "timezone" setting in Settings.
     */
    function display_timezone(): string
    {
        return Setting::get('timezone', config('app.display_timezone', 'UTC'));
    }
}

if (! function_exists('display_date_format')) {
    /**
     * PHP date() format string used to render dates for display — via the
     * toDisplay() Carbon macro (with no explicit $format) and in API
     * responses' "*_display" fields. Set via the "date_format" setting in
     * Settings, e.g. "d M Y, h:i A" -> "08 Sep 2026, 08:59 AM".
     */
    function display_date_format(): string
    {
        return Setting::get('date_format', 'd M Y, h:i A');
    }
}

if (! function_exists('format_money')) {
    /**
     * Display amount in the site's configured currency — the symbol is the
     * "currency_symbol" setting (settings group "currency"), falling back to
     * ৳, matching the Admin Orders/Reports and ProductLabel views. Trailing
     * ".00" is stripped so whole amounts read cleanly on the storefront.
     *
     * Decimals honour the "decimal_places" currency setting (0 = whole numbers);
     * pass an explicit $decimals to override the site-wide value.
     */
    function format_money(mixed $amount, ?int $decimals = null): string
    {
        $symbol = Setting::get('currency_symbol', '৳');
        $decimals ??= (int) Setting::get('decimal_places', 2);
        $formatted = number_format((float) $amount, $decimals);

        if ($decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $symbol.' '.$formatted;
    }
}

if (! function_exists('cms_cards')) {
    /**
     * A CMS section's Cards, looked up by page slug + section Name (the
     * "Name" field on /admin/pages/{id}/cms) — for pulling repeatable
     * image/title/description tiles into a view without an Eloquent query.
     *
     * @return array<int, array{image: ?string, title: ?string, description: ?string}>
     */
    function cms_cards(string $page, string $name): array
    {
        $pageId = Page::where('slug', $page)->value('id');

        if (! $pageId) {
            return [];
        }

        return CmsSection::cachedForPage($pageId)->firstWhere('name', $name)?->localizedCards() ?? [];
    }
}

if (! function_exists('page_constant')) {
    /**
     * A single Constant value from a Page's own Constant editor (as opposed to
     * a CMS section's — see cms_constant()), looked up by page slug + key.
     */
    function page_constant(string $page, string $key): ?string
    {
        return Page::where('slug', $page)->first()?->constantMap()[$key] ?? null;
    }
}

if (! function_exists('cms_constant')) {
    /**
     * A single Constant value from a CMS section, looked up by page slug +
     * section Name + Constant key.
     */
    function cms_constant(string $page, string $name, string $key): ?string
    {
        $pageId = Page::where('slug', $page)->value('id');

        if (! $pageId) {
            return null;
        }

        return CmsSection::cachedForPage($pageId)->firstWhere('name', $name)?->constantMap()[$key] ?? null;
    }
}

if (! function_exists('setting_constant')) {
    /**
     * A single site-wide Constant value, looked up by key — set from
     * Settings → Other → Constant, not tied to any Page or CMS section.
     */
    function setting_constant(string $key): ?string
    {
        $constants = json_decode(Setting::get('constants', '[]') ?: '[]', true) ?: [];

        return collect($constants)->firstWhere('key', $key)['value'] ?? null;
    }
}

/*
|--------------------------------------------------------------------------
| Theme helpers
|--------------------------------------------------------------------------
|
| Every theme reads its own theme.json the same way, so the reading is a helper
| rather than a pattern each theme reimplements. A theme's templates ask for
| "hero_title" and get the active theme's own value, with no slug repeated on
| every line and no knowledge of how the file is keyed or where it lives:
|
|     {{ theme_setting('hero_title') }}
|     @foreach (theme_rows('projects') as $project) ... @endforeach
|
| Pass a slug as the last argument to read another theme's file, which is what
| a shared partial does when one partial serves more than one theme.
|
| Underneath these are ThemeSettings (the file itself) and Themes (which theme
| is active, and which templates it ships). Both are still the right thing to
| call directly from PHP; the helpers exist so that a template does not have to
| name either of them.
|
*/

if (! function_exists('theme_slug')) {
    /**
     * The slug of the theme the public site is currently rendering — the folder
     * name under themes/, which is also the middle
     * part of every `theme_{slug}_*` key in that theme's theme.json.
     */
    function theme_slug(): string
    {
        return Themes::active();
    }
}

if (! function_exists('theme_setting_key')) {
    /**
     * The key a theme's field is stored under, for a template that has to build
     * a key itself (an id/for pair, a query string, a test assertion).
     *
     * Accepts either form: a bare "hero_title" becomes
     * "theme_{active}_hero_title", and a key that already carries the prefix is
     * returned unchanged.
     */
    function theme_setting_key(string $field, ?string $slug = null): string
    {
        return ThemeSettings::keyFor($field, $slug);
    }
}

if (! function_exists('theme_json')) {
    /**
     * A theme's whole theme.json, manifest fields and settings alike, as
     * key => value. Absent, empty and unparseable files all read as an empty
     * array rather than as an error, so a theme that ships no file is simply a
     * theme with nothing configured yet.
     *
     * @return array<string, mixed>
     */
    function theme_json(?string $slug = null): array
    {
        return ThemeSettings::all($slug);
    }
}

if (! function_exists('theme_manifest')) {
    /**
     * A theme's manifest — name, description, version, author, tags — with
     * sensible fallbacks for anything the file does not say.
     *
     * @return array{name: string, description: string, version: string, author: string, tags: array<int, string>}
     */
    function theme_manifest(?string $slug = null): array
    {
        return Themes::manifest($slug ?? Themes::active());
    }
}

if (! function_exists('theme_name')) {
    /**
     * A theme's display name, falling back to its slug in title case.
     */
    function theme_name(?string $slug = null): string
    {
        return (string) theme_manifest($slug)['name'];
    }
}

if (! function_exists('theme_setting')) {
    /**
     * One of a theme's own settings, as a trimmed string.
     *
     *     {{ theme_setting('hero_title') }}
     *     {{ theme_setting('hero_title', 'Untitled') }}
     *     {{ theme_setting('hero_title', '', 'portfolio') }}   // another theme's file
     *
     * Blank is not missing: a key the owner saved empty comes back as an empty
     * string, which is what lets a template tell "left blank on purpose" from
     * "this theme has no such field" with @if. Ask for a $default when either
     * should fall back to the same thing.
     */
    function theme_setting(string $field, string $default = '', ?string $slug = null): string
    {
        return ThemeSettings::text($slug, $field, $default);
    }
}

if (! function_exists('theme_setting_raw')) {
    /**
     * One of a theme's own settings with its JSON type intact — a list, a
     * number, a boolean — for the rare field that is not a string.
     * Prefer theme_rows() for a list of records, or theme_setting() for text.
     */
    function theme_setting_raw(string $field, mixed $default = null, ?string $slug = null): mixed
    {
        return ThemeSettings::get($slug, $field, $default);
    }
}

if (! function_exists('theme_rows')) {
    /**
     * One of a theme's list settings, as a plain list of flat string maps —
     * the read side of a <x-admin-repeatable-fields> declaration.
     *
     *     @foreach (theme_rows('projects') as $project)
     *         {{ $project['title'] }}
     *
     *     @endforeach
     *
     * An empty list is a valid state, not an error, and is the honest one: a
     * theme author who has not added any projects yet gets no rows back, so a
     * template can leave the whole section out rather than render a heading over
     * nothing.
     *
     * $identity is the field a bare string stands for, and only the caller knows
     * it: pass 'title' and a hand-edited ["Freelance", "Contract"] education
     * list stays readable as two one-field rows instead of being dropped. Leave
     * it out to take strictly what the file says.
     *
     * @return array<int, array<string, string>>
     */
    function theme_rows(string $field, ?string $identity = null, ?string $slug = null): array
    {
        return ThemeSettings::rows($slug, $field, $identity);
    }
}

if (! function_exists('theme_color')) {
    /**
     * One of a theme's colour settings as a CSS colour, or $fallback when it is
     * blank or is not a hex value.
     *
     *     :style="'--color-brand: '.theme_color('accent_color', '#045b30')"
     *
     * Every colour a theme stores is owner-typed or owner-pasted, so the shape
     * check is the point: a value that is not a colour becomes "unset" and the
     * stylesheet's own default wins, rather than a :root block that silently
     * stops applying because of one bad paste.
     */
    function theme_color(string $field, ?string $fallback = null, ?string $slug = null): ?string
    {
        $value = theme_setting($field, '', $slug);

        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1 ? $value : $fallback;
    }
}
