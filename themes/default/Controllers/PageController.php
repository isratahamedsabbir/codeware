<?php

namespace Themes\Default\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersStandalonePage;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "default" theme's standalone pages — About, Contact, FAQ and whatever
 * else the admin has published, at the three whitelisted slugs
 * themes/default/routes/web.php registers.
 *
 * A theme with no page.blade.php registers no `page` route at all, so this
 * class exists for the default theme (and the ecommerce theme, which has its
 * own copy) but not for the portfolio — see Themes/Portfolio/, which has no
 * equivalent.
 */
class PageController extends ThemeController
{
    use RendersStandalonePage;
}
