<?php

namespace App\Http\Controllers;

use App\Support\Seo\Sitemap;
use Illuminate\Http\Response;

/**
 * The sitemap, served rather than stored.
 */
class SitemapController extends Controller
{
    public function __invoke(?string $group = null): Response
    {
        abort_if($group !== null && ! in_array($group, Sitemap::GROUPS, true), 404);

        $headers = [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Link' => '<' . url('/favicon.ico') . '>; rel="icon"',
        ];

        return response(Sitemap::xml($group), 200, $headers);
    }
}
