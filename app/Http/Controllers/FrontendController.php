<?php

namespace App\Http\Controllers;

use App\Models\CmsSection;
use App\Models\Page;
use App\Models\Setting;
use App\Support\Themes;

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
            ? CmsSection::active()->ofPage($homePage->id)->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        return view("frontend.themes.{$theme}.home", [
            'sections' => $sections,
            'title' => Setting::get('seo_meta_title') ?: Setting::get('site_name'),
            'navPages' => $this->navPages(),
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

        $sections = CmsSection::active()->ofPage($page->id)->orderBy('sort_order')->orderBy('id')->get();

        return view("frontend.themes.{$theme}.page", [
            'page' => $page,
            'sections' => $sections,
            'title' => $page->seo_title ?: $page->getTranslation('title', 'en', false),
            'navPages' => $this->navPages(),
            'currentSlug' => $slug,
        ]);
    }

    /**
     * Every standalone page (Home, About, Contact, FAQ, ...), in the admin's
     * chosen order — the same list every theme's header renders as its site nav,
     * so adding/reordering pages in the admin updates every theme at once.
     */
    private function navPages()
    {
        return Page::ofType('page')->published()->orderBy('sort_order')->get();
    }
}
