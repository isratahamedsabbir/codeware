<?php

namespace App\Support\Seo;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Themes;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

/**
 * The URL inventory a crawler is invited to read, built from the content tables.
 *
 * This replaces a sitemap written to public/sitemap.xml by an admin button, which
 * had three problems worth naming. It went stale the moment anything was published
 * or unpublished; it built its addresses from config('app.frontend_url'), the
 * Next.js dev server, so every URL in it pointed at a host the canonicals never
 * claimed; and it listed /products/category/{slug} and /blog/category/{slug},
 * which are not routes this application has, so a good share of the file was
 * 404s advertised as inventory.
 *
 * Two rules run through it. A page the site has told a crawler to keep out of its
 * index (Page::no_index, and the routes that are noindex by policy) is left out
 * entirely — a sitemap that invites a crawler to a noindex URL is asking for the
 * two signals to be believed equally. And a URL the active theme cannot serve is
 * left out, because the same site is a portfolio under one theme and a shop under
 * another, and a sitemap that does not know which is a lie either way.
 *
 * hreflang alternates are deliberately absent rather than wrong: they need a
 * locale-prefixed URL to point at, and the storefront has no such route yet.
 * Emitting them today would advertise /bn/... addresses that 404.
 */
final class Sitemap
{
    /**
     * How often each kind of page tends to change, and how much it matters.
     *
     * Crawlers are free to ignore both. They are here because an absent
     * changefreq is read as "never changes" by at least some crawlers, and a
     * site-wide 0.5 says nothing about which of its pages are the point.
     */
    private const SIGNALS = [
        'home' => ['changefreq' => 'daily', 'priority' => 1.0],
        'listing' => ['changefreq' => 'weekly', 'priority' => 0.8],
        'product' => ['changefreq' => 'weekly', 'priority' => 0.7],
        'post' => ['changefreq' => 'monthly', 'priority' => 0.6],
        'page' => ['changefreq' => 'monthly', 'priority' => 0.6],
    ];

    /**
     * The sitemap as XML, cached against the data it was built from.
     *
     * The cache key is the content's own fingerprint rather than a fixed name,
     * so publishing a product produces a different key and the next request
     * rebuilds it. That self-invalidates without an observer on every model and
     * without the "sitemap is three weeks out of date" failure mode a
     * regenerate-by-hand button has. The superseded keys expire on their own.
     */
    public static function xml(): string
    {
        return Cache::remember(
            'seo:sitemap:'.self::fingerprint(),
            now()->addDay(),
            static fn () => self::toXml(self::entries())
        );
    }

    /**
     * The URL inventory: loc, lastmod, changefreq, priority, in that order.
     *
     * @return array<int, array{loc: string, lastmod: ?string, changefreq: string, priority: float}>
     */
    public static function entries(): array
    {
        $entries = array_merge(
            [self::entry(Url::url('/'), null, 'home')],
            self::listing(Url::url('shop'), 'shop'),
            self::listing(Url::url('blog'), 'blog'),
            self::pages(),
            self::products(),
            self::productCategories(),
            self::posts(),
        );

        // A page can be reachable under more than one row (a Page that is also a
        // category, say), and a URL listed twice reads as two pages to a crawler.
        $unique = [];

        foreach ($entries as $entry) {
            $unique[$entry['loc']] = $entry;
        }

        return array_values($unique);
    }

    /**
     * What the XML should say, kept separate so the admin screen can show a
     * human-readable preview of the same bytes a crawler gets.
     *
     * @param  array<int, array{loc: string, lastmod: ?string, changefreq: string, priority: float}>  $entries
     */
    public static function toXml(array $entries): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($entries as $entry) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.self::escape($entry['loc'])."</loc>\n";

            if ($entry['lastmod']) {
                $xml .= '    <lastmod>'.self::escape($entry['lastmod'])."</lastmod>\n";
            }

            $xml .= '    <changefreq>'.$entry['changefreq']."</changefreq>\n";
            $xml .= '    <priority>'.rtrim(rtrim(number_format($entry['priority'], 1, '.', ''), '0'), '.')."</priority>\n";
            $xml .= "  </url>\n";
        }

        return $xml."</urlset>\n";
    }

    /**
     * A cheap summary of everything the sitemap is built from.
     *
     * Deliberately counts and timestamps rather than hashing rows: this runs on
     * every uncached request, and a full-table digest would cost more than the
     * build it is trying to avoid.
     */
    private static function fingerprint(): string
    {
        $parts = [Themes::active(), Url::origin()];

        foreach ([Page::class, Product::class, Post::class, ProductCategory::class] as $model) {
            $parts[] = $model::count();
            $parts[] = (string) ($model::max('updated_at') ?? '');
        }

        return md5(implode('|', $parts));
    }

    /**
     * A theme-level page: the shop index, the blog index.
     *
     * @return array<int, array{loc: string, lastmod: ?string, changefreq: string, priority: float}>
     */
    private static function listing(string $loc, string $routeName): array
    {
        return Themes::activeThemeCanRender($routeName) ? [self::entry($loc, null, 'listing')] : [];
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, changefreq: string, priority: float}>
     */
    private static function posts(): array
    {
        if (! Themes::activeThemeCanRender('blog.post')) {
            return [];
        }

        return Post::published()
            ->with('page:id,post_id,slug,no_index,updated_at')
            ->get()
            ->flatMap(fn (Post $post) => self::child(
                'blog/'.$post->page?->slug,
                $post->page?->no_index ?? false,
                self::newest($post->updated_at, $post->page?->updated_at),
                'post',
            ))
            ->all();
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, changefreq: string, priority: float}>
     */
    private static function products(): array
    {
        if (! Themes::activeThemeCanRender('products.show')) {
            return [];
        }

        return Product::active()
            ->with('page:id,product_id,slug,no_index,updated_at')
            ->get()
            ->flatMap(fn (Product $product) => self::child(
                'products/'.$product->page?->slug,
                $product->page?->no_index ?? false,
                self::newest($product->updated_at, $product->page?->updated_at),
                'product',
            ))
            ->all();
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, changefreq: string, priority: float}>
     */
    private static function productCategories(): array
    {
        if (! Themes::activeThemeCanRender('shop.category')) {
            return [];
        }

        return ProductCategory::where('status', 'active')
            ->with('page:id,category_id,slug,no_index,updated_at')
            ->get()
            ->flatMap(fn (ProductCategory $category) => self::child(
                'category/'.$category->page?->slug,
                $category->page?->no_index ?? false,
                self::newest($category->updated_at, $category->page?->updated_at),
                'listing',
            ))
            ->all();
    }

    /**
     * Standalone CMS pages.
     *
     * Excludes the home page: it is already the first entry, and the /home alias
     * is the same content at a second address — one page, one entry.
     *
     * @return array<int, array{loc: string, lastmod: ?string, changefreq: string, priority: float}>
     */
    private static function pages(): array
    {
        return Page::published()
            ->where('type', 'page')
            ->where('slug', '!=', 'home')
            ->where('no_index', false)
            ->get(['slug', 'updated_at'])
            ->map(fn (Page $page) => self::entry(Url::url($page->slug), $page->updated_at, 'page'))
            ->all();
    }

    /**
     * An entry for content that hangs off a paired Page row, where the Page is
     * the thing that decides whether the URL may be indexed.
     *
     * A null slug means the row has no paired Page yet, so there is no address
     * this site can serve it at — dropped rather than guessed at.
     *
     * @return array<int, array{loc: string, lastmod: ?string, changefreq: string, priority: float}>
     */
    private static function child(?string $path, bool $noIndex, mixed $updatedAt, string $signal): array
    {
        if ($path === null || $noIndex) {
            return [];
        }

        return [self::entry(Url::url($path), $updatedAt, $signal)];
    }

    /**
     * The more recent of a content row and its paired Page.
     *
     * Either can be edited without the other, and lastmod is a claim about when
     * the page last changed — the later of the two edits is that moment.
     *
     * Typed on DateTimeInterface rather than a Carbon class on purpose: this
     * application casts timestamps to CarbonImmutable, and a narrower check than
     * that does not fail loudly, it just quietly drops every lastmod in the file.
     */
    private static function newest(mixed ...$stamps): ?DateTimeInterface
    {
        $known = array_filter($stamps, fn ($stamp) => $stamp instanceof DateTimeInterface);

        if ($known === []) {
            return null;
        }

        return max($known);
    }

    /**
     * @return array{loc: string, lastmod: ?string, changefreq: string, priority: float}
     */
    private static function entry(string $loc, mixed $updatedAt, string $signal): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $updatedAt instanceof DateTimeInterface ? $updatedAt->format(DATE_ATOM) : null,
            'changefreq' => self::SIGNALS[$signal]['changefreq'],
            'priority' => self::SIGNALS[$signal]['priority'],
        ];
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
