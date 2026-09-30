<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\CmsSection;
use App\Support\Frontend;

/**
 * The homepage: the public site root, populated with the "home" page's CMS
 * sections.
 *
 * Split out of the old application-wide FrontendController and shared by every
 * theme that ships a home template, so the *data* a homepage needs is stated
 * once. What a theme's homepage looks like was always the template's own job
 * (resources/views/frontend/themes/{slug}/home.blade.php) and still is — this
 * only decides what it is handed.
 */
trait RendersHomePage
{
    public function home()
    {
        $homePage = Frontend::homePage();

        $sections = $homePage
            ? CmsSection::cachedForPage($homePage->id)
            : collect();

        return $this->view('home', [
            'page' => $homePage,
            'sections' => $sections,
            // The page name only. The site root's <title> is the Global SEO title
            // (or the home page's own seo_title), which SeoResolver picks and
            // deliberately does not run through the "%s | Site Name" template.
            'title' => $homePage?->title,
            'currentSlug' => 'home',
        ]);
    }
}
