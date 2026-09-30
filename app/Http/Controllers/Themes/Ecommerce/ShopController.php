<?php

namespace App\Http\Controllers\Themes\Ecommerce;

use App\Http\Controllers\Themes\Concerns\RendersCatalog;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "ecommerce" theme's catalog: the shop listing and the category, brand and
 * tag pages that filter it.
 *
 * The only theme with a shop — the portfolio is a one-pager and the default
 * theme a small site, neither of which has a product listing, so neither has
 * this file. The sidebar facets, the filter behaviour and the sort options are
 * this theme's shop.blade.php and its partials; what is decided here is which
 * products a request resolves to.
 */
class ShopController extends ThemeController
{
    use RendersCatalog;
}
