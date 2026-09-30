<?php

namespace App\Http\Controllers\Themes\Ecommerce;

use App\Http\Controllers\Themes\Concerns\RendersHomePage;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "ecommerce" theme's homepage.
 *
 * One of the six files that make up this theme's storefront. All of them were
 * FrontendController before, a class of 600 lines shared by every theme, which
 * meant a change aimed at the shop could break the portfolio one-pager and
 * neither theme's pages were readable without scanning the whole file. Now a
 * theme's public surface is its own folder: this one plus PageController,
 * ShopController, ProductController, BlogController, CartController and
 * AccountController.
 */
class HomeController extends ThemeController
{
    use RendersHomePage;
}
