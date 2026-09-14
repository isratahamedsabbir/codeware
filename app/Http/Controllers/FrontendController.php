<?php

namespace App\Http\Controllers;

use App\Models\CmsSection;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\ProductVendor;
use App\Models\Setting;
use App\Support\Themes;
use Spatie\Permission\Models\Role;

class FrontendController extends Controller
{
    /**
     * The public site root — renders the admin-selected theme's homepage,
     * populated with the "home" page's CMS sections.
     */
    public function home()
    {
        $theme = Themes::active();

        $homePage = Page::where('slug', 'home')->first();

        $sections = $homePage
            ? CmsSection::cachedForPage($homePage->id)
            : collect();

        return view("frontend.themes.{$theme}.home", [
            'sections' => $sections,
            'title' => Setting::get('seo_meta_title') ?: Setting::get('site_name'),
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => 'home',
            'showVendorLogin' => $this->showVendorLogin(),
        ]);
    }

    /**
     * Any other standalone page (About, Contact, FAQ, ...) — same rendering as
     * home(), just scoped to the requested page's own CMS sections instead of
     * the "home" page's. Same view (`page.blade.php`) across every theme.
     */
    public function page(string $slug)
    {
        $theme = Themes::active();

        $page = Page::where('slug', $slug)->where('type', 'page')->where('status', 'active')->firstOrFail();

        $sections = CmsSection::cachedForPage($page->id);

        return view("frontend.themes.{$theme}.page", [
            'page' => $page,
            'sections' => $sections,
            'title' => $page->seo_title ?: $page->getTranslation('title', 'en', false),
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => $slug,
            'showVendorLogin' => $this->showVendorLogin(),
        ]);
    }

    /**
     * Every standalone page (Home, About, Contact, FAQ, ...), in the admin's
     * chosen order — used as the site nav by the "default" theme, so
     * adding/reordering pages in the admin updates it automatically.
     */
    private function navPages()
    {
        return Page::ofType('page')->published()->orderBy('sort_order')->get();
    }

    /**
     * The "Frontend" menu (see FrontendMenuSeeder, and /admin/menu), managed
     * by hand rather than auto-generated from the page list — used as the site
     * nav by the portfolio and ecommerce themes.
     */
    private function frontendMenuItems()
    {
        return MenuItem::where('group', 'frontend')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Whether the Vendor Login link should appear — hidden whenever nobody
     * could actually sign into the vendor portal: the 'vendor' role itself
     * deactivated (see Roles\Index::toggleStatus, access-vendor-portal gate)
     * or no active vendor exists for a user to be assigned to.
     */
    private function showVendorLogin(): bool
    {
        $vendorRoleActive = Role::where('name', 'vendor')->where('status', 'active')->exists();

        return $vendorRoleActive && ProductVendor::active()->exists();
    }
}
