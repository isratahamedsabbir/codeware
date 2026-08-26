<?php

namespace App\Http\Controllers;

use App\Models\CmsSection;
use App\Models\Setting;
use App\Support\Themes;

class FrontendController extends Controller
{
    /**
     * The public site root — renders the admin-selected theme's homepage,
     * populated with the active "home" page CMS sections.
     */
    public function home()
    {
        $theme = Themes::active();

        $sections = CmsSection::active()->ofPage('home')->orderBy('id')->get();

        return view("frontend.themes.{$theme}.home", [
            'sections' => $sections,
            'title' => Setting::get('seo_meta_title') ?: Setting::get('site_name'),
        ]);
    }
}
