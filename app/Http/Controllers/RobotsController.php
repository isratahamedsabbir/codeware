<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Seo\Url;
use Illuminate\Http\Response;

/**
 * robots.txt, served rather than stored.
 *
 * Two parts, and only the first is the admin's to edit. The rules come from the
 * `seo_robots_txt` setting; the Sitemap: line is appended here every time, so it
 * cannot be deleted by an admin who clears the textarea, and cannot be pointed
 * at a host that isn't this site — the one line in this file that has to be
 * right for crawlers to find the rest of the site's inventory at all.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        return response($this->body(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
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
