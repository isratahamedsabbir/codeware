<?php

namespace Themes\Portfolio\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersHomePage;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "portfolio" theme's one-pager.
 *
 * One of the two files that make up this theme's storefront (the other is
 * BlogController). There is deliberately no PageController, no ShopController
 * and no AccountController: the portfolio's nav is section anchors
 * ("#projects", "#skills", see Frontend::portfolioMenuItems()) into the single
 * page this serves, so /about, /shop and /account are not this theme's routes
 * at all and asking for one gets the portfolio's own 404.
 *
 * The sections themselves — the hero, the project grid, the timeline — are this
 * theme's home.blade.php, which is why this file is as short as it is.
 */
class HomeController extends ThemeController
{
    use RendersHomePage;
}
