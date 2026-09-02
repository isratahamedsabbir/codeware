<?php

namespace App\Http\Controllers;

use App\Models\CmsSection;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Support\Themes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

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
            ? $this->cachedSections($homePage->id)
            : collect();

        return view("frontend.themes.{$theme}.home", [
            'sections' => $sections,
            'title' => Setting::get('seo_meta_title') ?: Setting::get('site_name'),
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => 'home',
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

        $sections = $this->cachedSections($page->id);

        return view("frontend.themes.{$theme}.page", [
            'page' => $page,
            'sections' => $sections,
            'title' => $page->seo_title ?: $page->getTranslation('title', 'en', false),
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => $slug,
        ]);
    }

    /**
     * A page's active CMS sections, cached in Redis (tag 'cms', kept forever) —
     * the same tag CmsController's public API responses use, so
     * CmsSection::flushCache() invalidates both on every write. Caches the
     * *raw* attribute form (getAttributes(), not toArray()) and re-hydrates
     * into real models on read, so callers still get full CmsSection
     * instances with casts and methods like localizedCards()/metadataMap()
     * intact — never cache Eloquent objects/collections directly (see
     * MenuItem::menuCached()), and never toArray(): that decodes the
     * 'cards'/'metadata' JSON casts into plain arrays, which then blow up
     * when hydrate() re-applies the same cast on read (double-decoding a
     * PHP array instead of the JSON string it expects).
     *
     * @return Collection<int, CmsSection>
     */
    private function cachedSections(int $pageId): Collection
    {
        $rows = Cache::store('redis')->tags(['cms'])->rememberForever(
            "cms:page:{$pageId}:sections",
            fn () => CmsSection::active()->ofPage($pageId)->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (CmsSection $section) => $section->getAttributes())->all(),
        );

        return CmsSection::hydrate($rows);
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
}
