<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Seo\Url;
use Illuminate\Http\Response;

/**
 * robots.txt, served rather than stored.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $headers = [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Link' => '<' . url('/favicon.ico') . '>; rel="icon"',
        ];

        return response($this->body(), 200, $headers);
    }

    private function body(): string
    {
        $rules = trim((string) Setting::get('seo_robots_txt', ''));

        if ($rules === '') {
            // A blank textarea is not "crawl nothing" — that is a site-wide
            // deindexing left sitting in a box someone can save. An unset
            // robots.txt means exactly what a missing one does: no opinion.
            $rules = "User-agent: *\nDisallow:";
        }

        return rtrim($rules)."\n\nSitemap: ".Url::url('sitemap.xml')."\n";
    }
}
