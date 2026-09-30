<?php

namespace App\Http\Controllers\Themes\Default;

use App\Http\Controllers\Themes\Concerns\RendersHomePage;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "default" theme's homepage.
 *
 * This class is the whole of that page's public surface, and it is one of two
 * files that make up the theme's storefront (the other is PageController).
 * Both were FrontendController before — a class of 600 lines that also answered
 * for the ecommerce theme's shop and the portfolio theme's blog — so a theme's
 * pages were spread across files named after the application rather than the
 * theme. The other themes' equivalents are Themes/Ecommerce/ and
 * Themes/Portfolio/.
 *
 * The method itself comes from a trait rather than being written out, because
 * the *data* a homepage needs is the same on every theme — what differs is the
 * template, and that has always been this theme's own
 * resources/views/frontend/themes/default/home.blade.php. A theme that wanted a
 * different home overrides the method here.
 */
class HomeController extends ThemeController
{
    use RendersHomePage;
}
