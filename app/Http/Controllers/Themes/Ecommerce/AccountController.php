<?php

namespace App\Http\Controllers\Themes\Ecommerce;

use App\Http\Controllers\Themes\Concerns\RendersAccount;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "ecommerce" theme's customer account: dashboard, order history, order
 * detail and profile editing.
 *
 * Orders are matched to the user by user_id first, then by their email, so an
 * order placed before this account existed still shows up here for the customer
 * who later registers with the same address.
 *
 * This file is the whole of the account area, and its being here rather than in
 * the theme-agnostic controller namespace is what makes the feature's scope
 * obvious: the account pages are an ecommerce-store thing, and any other theme
 * that wants them adds a file to its own folder that uses the same trait.
 */
class AccountController extends ThemeController
{
    use RendersAccount;
}
