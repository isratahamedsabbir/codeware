<?php

namespace App\Http\Controllers\Themes\Ecommerce;

use App\Http\Controllers\Themes\Concerns\RendersProduct;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "ecommerce" theme's product detail page, plus the two storefront pages
 * that hang off a product: the favorites list and the advertisement click
 * tracker that sends a visitor on to a banner's destination.
 *
 * Gallery, variations, related products and the buy box are this theme's
 * product.blade.php; what is decided here is the product a slug resolves to and
 * which products count as related.
 */
class ProductController extends ThemeController
{
    use RendersProduct;
}
