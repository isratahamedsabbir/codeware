<?php

use App\Models\CmsSection;
use App\Models\Page;
use App\Models\Setting;

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
