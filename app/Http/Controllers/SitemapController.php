<?php

namespace App\Http\Controllers;

use App\Support\Seo\Sitemap;
use Illuminate\Http\Response;

/**
 * The sitemap, served rather than stored.
 *
 * The reason this is a route and not a file in public/ is that a file is only as
 * current as the last time somebody remembered to press a button, and a sitemap
 * that has quietly gone stale is worse than none: a crawler reads it as a
 * complete inventory and stops discovering pages on its own.
 *
 * Kept out of the theme route files deliberately. The sitemap describes the site
 * rather than one theme's presentation of it, and it has to keep answering when
 * the active theme is a portfolio with no product or blog templates at all.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        return response(Sitemap::xml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
