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

if (! function_exists('page_content')) {
    /**
     * A single Content value from a Page's own Content editor (as opposed to
     * a CMS section's — see cms_content()), looked up by page slug + key.
     */
    function page_content(string $page, string $key): ?string
    {
        return Page::where('slug', $page)->first()?->metadataMap()[$key] ?? null;
    }
}

if (! function_exists('cms_content')) {
    /**
     * A single Content value from a CMS section, looked up by page slug +
     * section Name + Content key.
     */
    function cms_content(string $page, string $name, string $key): ?string
    {
        $pageId = Page::where('slug', $page)->value('id');

        if (! $pageId) {
            return null;
        }

        return CmsSection::cachedForPage($pageId)->firstWhere('name', $name)?->contentMap()[$key] ?? null;
    }
}
