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
        ]);
    }
}
