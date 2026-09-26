<?php

namespace App\Livewire\Admin\Advance;

use App\Support\Seo\Sitemap as SitemapBuilder;
use Livewire\Component;

/**
 * A read-only window onto the live sitemap.
 *
 * There is no Generate button any more, and that is the point. The sitemap used
 * to be a file this screen wrote, which meant it was only as current as the last
 * time an admin remembered to press it, and it built its addresses from
 * config('app.frontend_url') — the Next.js dev server — so every URL in it
 * pointed somewhere the site does not live. It is now built per request from the
 * content tables by App\Support\Seo\Sitemap, so there is nothing to generate and
 * nothing to forget; what this screen shows is the same bytes a crawler gets.
 */
class Sitemap extends Component
{
    public function render()
    {
        $entries = SitemapBuilder::entries();

        return view('livewire.admin.advance.sitemap', [
            'urlCount' => count($entries),
            'sitemapUrl' => route('sitemap'),
            'entries' => $entries,
        ])->layout('layouts.admin', ['title' => 'Sitemap']);
    }
}
