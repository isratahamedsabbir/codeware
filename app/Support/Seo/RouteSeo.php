<?php

namespace App\Support\Seo;

/**
 * What a route says about itself to a search engine, by route name.
 *
 * Two things live here that used to be either absent or hardcoded:
 *
 *  - og:type. It was "website" on every page, including product and post pages,
 *    which is what stops a link unfurling as a shop item or an article.
 *  - indexability. A cart, a checkout, an order confirmation, a favourites list
 *    and the whole customer account area were all rendering as indexable pages
 *    with a self-referential canonical, because the robots tag was only ever
 *    emitted when a Page had no_index set and those routes pass no Page at all.
 *
 * Keyed by route name rather than by URL, because the storefront's routes are
 * per-theme and the same name means the same page in every theme file (see
 * routes/web/default.php and Themes::ROUTE_TEMPLATES).
 *
 * A name with no profile here is a route that renders no themed template —
 * an alias, the click tracker — so it is neither indexed deliberately nor
 * excluded deliberately, and gets the neutral default.
 */
final class RouteSeo
{
    /**
     * priority/changefreq are the sitemap's, not the meta tags'; keeping them on
     * the same row as indexability means one place answers "what kind of URL is
     * this" for both the head and the sitemap. Google's own guidance is that
     * Google ignores priority/changefreq, so these exist for the other engines
     * and for the humans reading the sitemap.
     */
    private const PROFILES = [
        'home' => ['type' => 'website', 'index' => true, 'priority' => '1.0', 'changefreq' => 'daily'],
        'page' => ['type' => 'website', 'index' => true, 'priority' => '0.8', 'changefreq' => 'monthly'],
        'shop' => ['type' => 'website', 'index' => true, 'priority' => '0.9', 'changefreq' => 'daily'],
        'shop.category' => ['type' => 'website', 'index' => true, 'priority' => '0.8', 'changefreq' => 'weekly'],
        'shop.brand' => ['type' => 'website', 'index' => true, 'priority' => '0.6', 'changefreq' => 'weekly'],
        'shop.tag' => ['type' => 'website', 'index' => true, 'priority' => '0.4', 'changefreq' => 'weekly'],
        'products.show' => ['type' => 'product', 'index' => true, 'priority' => '0.8', 'changefreq' => 'weekly'],
        'blog' => ['type' => 'website', 'index' => true, 'priority' => '0.7', 'changefreq' => 'daily'],
        'blog.post' => ['type' => 'article', 'index' => true, 'priority' => '0.7', 'changefreq' => 'monthly'],

        // A visitor's own state, not a page of the site: indexing any of these
        // puts a customer's basket, order history or profile into a search index.
        'cart' => ['type' => 'website', 'index' => false, 'priority' => '0.0', 'changefreq' => 'yearly'],
        'checkout' => ['type' => 'website', 'index' => false, 'priority' => '0.0', 'changefreq' => 'yearly'],
        'checkout.confirmation' => ['type' => 'website', 'index' => false, 'priority' => '0.0', 'changefreq' => 'yearly'],
        'favorites' => ['type' => 'website', 'index' => false, 'priority' => '0.0', 'changefreq' => 'yearly'],
        'account.dashboard' => ['type' => 'website', 'index' => false, 'priority' => '0.0', 'changefreq' => 'yearly'],
        'account.orders' => ['type' => 'website', 'index' => false, 'priority' => '0.0', 'changefreq' => 'yearly'],
        'account.orders.show' => ['type' => 'website', 'index' => false, 'priority' => '0.0', 'changefreq' => 'yearly'],
        'account.profile' => ['type' => 'website', 'index' => false, 'priority' => '0.0', 'changefreq' => 'yearly'],
    ];

    private const DEFAULT = ['type' => 'website', 'index' => true, 'priority' => '0.5', 'changefreq' => 'monthly'];

    /**
     * @return array{type: string, index: bool, priority: string, changefreq: string}
     */
    public static function for(?string $routeName): array
    {
        return self::PROFILES[$routeName] ?? self::DEFAULT;
    }

    public static function typeFor(?string $routeName): string
    {
        return self::for($routeName)['type'];
    }

    public static function isIndexable(?string $routeName): bool
    {
        return self::for($routeName)['index'];
    }

    /**
     * @return array<string, array{type: string, index: bool, priority: string, changefreq: string}>
     */
    public static function all(): array
    {
        return self::PROFILES;
    }
}
