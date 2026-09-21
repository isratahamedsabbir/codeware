<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\ProductVendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

/**
 * Shared, cached building blocks every public page needs (nav pages, the
 * frontend menu, the home page, the vendor-login flag) — used by both
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
     * @return Collection<int, Page>
     */
    public static function navPages(): Collection
    {
        $rows = ContentCache::remember('nav-pages', fn () => Page::ofType('page')->published()->orderBy('sort_order')->get()->map->getAttributes()->all());

        return Page::hydrate($rows);
    }

    /**
     * The "Frontend" menu (see FrontendMenuSeeder, and /admin/menu), managed
     * by hand rather than auto-generated from the page list — used as the site
     * nav by the portfolio and ecommerce themes.
     *
     * @return Collection<int, MenuItem>
     */
    public static function menuItems(): Collection
    {
        $rows = ContentCache::remember('frontend-menu', fn () => MenuItem::where('group', 'frontend')->where('is_active', true)->orderBy('sort_order')->get()->map->getAttributes()->all());

        return MenuItem::hydrate($rows);
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
}
