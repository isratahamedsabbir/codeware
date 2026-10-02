<?php

namespace Themes\Ecommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersStandalonePage;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "ecommerce" theme's standalone pages (About, Contact, FAQ) — the same
 * three whitelisted slugs the default theme serves, rendered in this theme's
 * page.blade.php.
 *
 * Deliberately a separate class from Themes/Default\PageController rather than
 * a shared one: the two themes' page templates are independent, and a theme
 * adding a slug to its route file should be able to answer it without the other
 * theme's file being involved at all.
 */
class PageController extends ThemeController
{
    use RendersStandalonePage;
}
