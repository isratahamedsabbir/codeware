<?php

namespace App\Support\Seo;

use App\Models\Page;
use App\Models\Setting;
use App\Support\Locale;
use Illuminate\Http\Request;

/**
 * Resolves one page's SEO into a SeoData object.
 *
 * Single source of truth for the fallback chain that used to live inline in
 * partials/seo-meta.blade.php:
 *
 *     per-page field  ->  global "Global SEO" setting  ->  sensible default
 *
 * Per-page fields live on the paired Page, which every Product, Post and
 * category is 1:1 with (App\Support\PageCascade), so "the product's SEO title"
 * is the product page's seo_title — see App\Concerns\HasSeoFields for the admin
 * side of that.
 *
 * Memoised per request, because two partials need it: partials/head.blade.php for
 * the <title> and partials/seo-meta.blade.php for everything else. They have to
 * agree, and resolving twice would be a second settings read per page.
 */
final class SeoResolver
{
    /**
     * The routes whose <title> *is* the site's own title rather than a page name
     * to be run through the title template.
     *
     * The site root and the catalog index are the one place the Global SEO title
     * is the whole answer: appending "%s | Site Name" to a title the admin typed
     * for the whole site just repeats the site name back at them. Everywhere
     * else the global title stays a fallback, below the page's own name.
     */
    private const GLOBAL_TITLE_ROUTES = ['home', 'shop'];

    /** @var array<string, SeoData> */
    private static array $memo = [];

    public static function resolve(Request $request, ?Page $page = null, ?string $title = null): SeoData
    {
        $key = spl_object_id($request).'|'.($page?->id ?? '-').'|'.($title ?? '-');

        return self::$memo[$key] ??= self::build($request, $page, $title);
    }

    /**
     * Drops the memo. Tests that change a setting mid-request need this; a real
     * request never does, since one request renders one page.
     */
    public static function flush(): void
    {
        self::$memo = [];
    }

    private static function build(Request $request, ?Page $page, ?string $title): SeoData
    {
        $routeName = $request->route()?->getName();
        $profile = RouteSeo::for($routeName);
        $siteName = Setting::get('site_name') ?: config('app.name', 'Laravel');
        $locale = Locale::current();

        // An explicit seo_title is the whole title. Suffixing it would push a
        // hand-tuned 58-character title over the ~60 that actually fit in a
        // SERP, and an admin who typed one meant it literally.
        $pageSeoTitle = self::pageValue($page, 'seo_title');
        $pageTitle = $pageSeoTitle
            ?: self::globalTitle($routeName)
            ?: self::applyTitleTemplate($title);

        $description = self::pageValue($page, 'seo_description')
            ?: Setting::translated('seo_meta_description')
            ?: Setting::get('site_description')
            ?: null;

        $canonical = self::canonical($request, $page);

        // og:title and twitter:title fall back to the page title rather than to
        // the $title the controller passed, so an entity that only set seo_title
        // still unfurls with that title on both cards.
        $ogTitle = self::pageValue($page, 'og_title')
            ?: Setting::translated('seo_og_title')
            ?: $pageTitle
            ?: null;

        $ogDescription = self::pageValue($page, 'og_description')
            ?: Setting::translated('seo_og_description')
            ?: $description;

        $ogImage = self::absoluteImage(
            self::pageValue($page, 'og_image') ?: Setting::get('seo_og_image')
        );

        $twitterImage = self::absoluteImage(
            self::pageValue($page, 'twitter_image') ?: Setting::get('seo_twitter_image') ?: $ogImage
        );

        return new SeoData(
            title: $pageTitle ?: $siteName,
            description: $description,
            canonical: $canonical,
            robots: self::robots($page, $routeName),
            ogType: $profile['type'],
            ogUrl: $canonical,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImage: $ogImage,
            ogImageAlt: $ogImage ? ($pageTitle ?: $siteName) : null,
            twitterCard: Setting::get('seo_twitter_card') ?: 'summary_large_image',
            twitterSite: Setting::get('seo_twitter_site') ?: null,
            twitterTitle: self::pageValue($page, 'twitter_title') ?: Setting::translated('seo_twitter_title') ?: $ogTitle,
            twitterDescription: self::pageValue($page, 'twitter_description') ?: Setting::translated('seo_twitter_description') ?: $ogDescription,
            twitterImage: $twitterImage,
            siteName: $siteName,
            locale: $locale,
        );
    }

    /**
     * The robots directives, in the order a crawler reads them.
     *
     * A route that isn't indexable is noindex whichever the Page says: a Page's
     * no_index can take a page out of the index, but a cart must not be able to
     * put itself back in.
     */
    private static function robots(?Page $page, ?string $routeName): array
    {
        $robots = [];

        if ((bool) ($page->no_index ?? false) || ! RouteSeo::isIndexable($routeName)) {
            $robots[] = 'noindex';
        }

        if ((bool) ($page->no_follow ?? false)) {
            $robots[] = 'nofollow';
        }

        // The admin's catch-all for the directives that aren't a per-page switch:
        // noarchive, max-snippet:-1, max-image-preview:large and friends.
        foreach (preg_split('/[,\r\n]+/', (string) Setting::get('seo_robots_extra', '')) ?: [] as $directive) {
            $directive = trim($directive);

            if ($directive !== '' && ! in_array($directive, $robots, true)) {
                $robots[] = $directive;
            }
        }

        return $robots;
    }

    /**
     * A translatable SEO field on the paired Page, read for the active locale.
     *
     * Branches on whether the Page actually declares the field translatable
     * rather than assuming: the SEO columns are plain strings today, and become
     * per-locale JSON as part of making SEO translatable, and this has to keep
     * resolving either way. getTranslation() throws on a non-translatable
     * attribute, so asking it about a plain column is not an option.
     */
    private static function pageValue(?Page $page, string $field): ?string
    {
        if (! $page) {
            return null;
        }

        $value = in_array($field, $page->getTranslatableAttributes(), true)
            ? ($page->getTranslation($field, Locale::current(), false)
                ?: ($page->getTranslations($field)[Locale::primary()] ?? null))
            : $page->{$field};

        return filled($value) ? (string) $value : null;
    }

    /**
     * The Global SEO title, but only on the routes that are the site rather than
     * a page within it. Per-locale, because the setting is a translatable one —
     * the home page in Bangla is titled from the Bangla copy, not the English.
     */
    private static function globalTitle(?string $routeName): ?string
    {
        if (! in_array($routeName, self::GLOBAL_TITLE_ROUTES, true)) {
            return null;
        }

        $global = Setting::translated('seo_meta_title');

        return filled($global) ? $global : null;
    }

    /**
     * The `<title>` when the entity didn't set one: the admin's template with the
     * page name in it, defaulting to "%s | Site Name". A template without a %s
     * would throw the page name away, so it is treated as a title-separator typo
     * and the name is appended anyway.
     */
    private static function applyTitleTemplate(?string $title): ?string
    {
        if (! filled($title)) {
            return null;
        }

        $siteName = Setting::get('site_name') ?: config('app.name', 'Laravel');
        $template = Setting::get('seo_title_template', '%s | '.$siteName) ?: '%s';

        return str_contains($template, '%s')
            ? str_replace('%s', $title, $template)
            : $title.' '.$template;
    }

    /**
     * The one URL that identifies this page.
     *
     * An admin-set canonical_base + canonical_slug wins outright — that is the
     * "this page also lives at a better address" escape hatch. Otherwise the
     * request's own path, rebuilt onto the configured origin, which is what makes
     * the same page emit the same canonical whether it was reached over http or
     * https, with or without www, and with tracking params attached.
     */
    private static function canonical(Request $request, ?Page $page): string
    {
        $base = self::pageValue($page, 'canonical_base');
        $slug = self::pageValue($page, 'canonical_slug');

        if (filled($base) && filled($slug)) {
            return Url::normalize(rtrim($base, '/').'/'.ltrim($slug, '/'));
        }

        return Url::origin().Url::path($request->path());
    }

    /**
     * An og:image / twitter:image has to be absolute — a relative path is
     * dropped by every scraper. Media may be stored either way (a MediaLibrary
     * row's path, or a full URL an admin pasted), so both are accepted.
     */
    private static function absoluteImage(?string $image): ?string
    {
        if (! filled($image)) {
            return null;
        }

        return str_starts_with($image, 'http://') || str_starts_with($image, 'https://')
            ? $image
            : url('/'.ltrim($image, '/'));
    }
}
