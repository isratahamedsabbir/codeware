<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\ProductVendor;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

/**
 * Shared, cached building blocks every public page needs (nav pages, the
 * frontend menu, the home page, the vendor/delivery login flags) — used by both
 * FrontendController and CustomerController so the theme's header/footer
 * partials get the same payload from a single source instead of duplicated
 * queries on every request.
 *
 * Page/MenuItem content is cached as plain attribute arrays and rehydrated on
 * every read, never as Eloquent objects — see Language::activeCached() for
 * why.
 */
class Frontend
{
    /**
     * Every standalone page (Home, About, Contact, FAQ, ...), in the admin's
     * chosen order — used as the site nav by the "default" theme.
     *
     * All of them render through the one page.blade.php of whichever theme is
     * active, so the theme either has standalone pages or has none: a theme
     * without that template 404s every one of these links, and the nav is
     * emptied rather than pointing the visitor at a dead end.
     *
     * @return Collection<int, Page>
     */
    public static function navPages(): Collection
    {
        $rows = ContentCache::remember('nav-pages', fn () => Page::ofType('page')->published()->orderBy('sort_order')->get()->map->getAttributes()->all());

        $pages = Page::hydrate($rows);

        return Themes::has('page') ? $pages : $pages->take(0);
    }

    /**
     * The "Frontend" menu (see FrontendMenuSeeder, and /admin/menu), managed
     * by hand rather than auto-generated from the page list — used as the site
     * nav by the ecommerce theme. The portfolio theme has a nav of its own,
     * see portfolioMenuItems().
     *
     * @return Collection<int, MenuItem>
     */
    public static function menuItems(): Collection
    {
        return static::renderableByTheme(static::menuCached('frontend-menu', 'frontend'));
    }

    /**
     * The "Portfolio" menu (see PortfolioMenuSeeder) — the portfolio theme's own
     * nav. Kept apart from menuItems() on purpose: the portfolio is a single
     * page, so its items are section anchors ("#projects", "#skills", ...) that
     * only mean anything there, while the ecommerce theme keeps menuItems().
     *
     * @return Collection<int, MenuItem>
     */
    public static function portfolioMenuItems(): Collection
    {
        return static::renderableByTheme(static::menuCached('portfolio-menu', 'portfolio'));
    }

    /**
     * The "Information" menu (see InformationMenuSeeder) — the About/Contact/FAQ
     * links rendered by the footer's Information column.
     *
     * @return Collection<int, MenuItem>
     */
    public static function informationMenu(): Collection
    {
        return static::renderableByTheme(static::menuCached('information-menu', 'information'));
    }

    /**
     * The "Quick Links" menu (see QuickLinksMenuSeeder) — the Home/Shop/shortcut
     * links rendered by the footer's Quick Links column.
     *
     * @return Collection<int, MenuItem>
     */
    public static function quickLinks(): Collection
    {
        return static::renderableByTheme(static::menuCached('quick-links', 'quick-links'));
    }

    /**
     * One storefront menu, read from the shared content cache as plain attribute
     * arrays and rehydrated on every read.
     *
     * @return Collection<int, MenuItem>
     */
    private static function menuCached(string $cacheKey, string $group): Collection
    {
        $rows = ContentCache::remember($cacheKey, fn () => MenuItem::where('group', $group)->where('is_active', true)->orderBy('sort_order')->get()->map->getAttributes()->all());

        return MenuItem::hydrate($rows);
    }

    /**
     * Drops the links the active theme can't render, so switching themes never
     * leaves a nav pointing into a page that theme doesn't have. Filtering
     * happens on the rehydrated collection rather than inside the cached query
     * because it depends on the active theme, which is per-request.
     *
     * @param  Collection<int, MenuItem>  $items
     * @return Collection<int, MenuItem>
     */
    private static function renderableByTheme(Collection $items): Collection
    {
        return $items->filter->isRenderableByCurrentTheme()->values();
    }

    /**
     * The "home" page — public root content, heavily reused across themes.
     */
    public static function homePage(): ?Page
    {
        $row = ContentCache::remember('home-page', fn () => Page::where('slug', 'home')->first()?->getAttributes());

        return $row ? Page::hydrate([$row])->first() : null;
    }

    /**
     * Whether the Vendor Login link should appear — hidden whenever nobody
     * could actually sign into the vendor portal: the 'vendor' role itself
     * deactivated or no active vendor exists for a user to be assigned to.
     * TTL'd rather than content-versioned: it depends on Role/ProductVendor
     * writes, which are too rare to justify bumping the shared version.
     */
    public static function showVendorLogin(): bool
    {
        return (bool) Cache::remember('frontend:show-vendor-login', 300, function () {
            $vendorRoleActive = Role::where('name', 'vendor')->where('status', 'active')->exists();

            return $vendorRoleActive && ProductVendor::active()->exists();
        });
    }

    /**
     * Whether the Delivery Login link should appear — hidden whenever nobody
     * could actually sign into the delivery portal: no rider left who is
     * unblocked, holds an active 'delivery_boy' role and none of the
     * ineligible ones (see User::scopeDeliveryBoys()). Deliberately not
     * TTL-cached like showVendorLogin(): blocking the last rider has to hide
     * the link straight away, and a single exists() query is cheap.
     */
    public static function showDeliveryLogin(): bool
    {
        return once(fn () => User::deliveryBoys()->exists());
    }
}
