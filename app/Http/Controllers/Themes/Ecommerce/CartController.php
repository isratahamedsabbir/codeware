<?php

namespace App\Http\Controllers\Themes\Ecommerce;

use App\Http\Controllers\Themes\Concerns\RendersCart;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "ecommerce" theme's cart, checkout and order confirmation.
 *
 * The only theme with a cart. All three pages are open to guests — a visitor
 * fills the checkout form in and pays on delivery without ever making an
 * account, and App\Services\OrderPlacement resolves the account from the email
 * they typed, so the order still turns up in that customer's history once they
 * can sign in. See RendersCart for the full reasoning.
 */
class CartController extends ThemeController
{
    use RendersCart;
}
