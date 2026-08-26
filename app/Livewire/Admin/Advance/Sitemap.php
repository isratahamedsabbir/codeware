<?php

namespace App\Livewire\Admin\Advance;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use SimpleXMLElement;

class Sitemap extends Component
{
    public ?int $urlCount = null;

    public ?string $generatedAt = null;

    public function mount(): void
    {
        $this->refreshStatus();
    }

    public function generate(): void
    {
        $urls = $this->collectUrls();

        File::put(public_path('sitemap.xml'), $this->buildXml($urls));

        AdminActivity::log('advance.sitemap.generate', 'Sitemap regenerated ('.count($urls).' URLs)');

        $this->refreshStatus();

        session()->flash('success', 'Sitemap generated with '.count($urls).' URLs.');
    }

    protected function refreshStatus(): void
    {
        $path = public_path('sitemap.xml');

        if (! File::exists($path)) {
            $this->urlCount = null;
            $this->generatedAt = null;

            return;
        }

        $this->urlCount = substr_count(File::get($path), '<loc>');
        $this->generatedAt = date('Y-m-d H:i:s', File::lastModified($path));
    }

    /**
     * Pulls every publicly reachable URL straight from the content tables (not the
     * Page model's SEO companion rows — those aren't guaranteed to exist for every
     * post/product/category). Path prefixes below are a best guess: the Next.js
     * frontend (codeware-frontend/) doesn't have real routes yet, so adjust these
     * once it does.
     *
     * @return array<int, array{loc: string, lastmod: ?string}>
     */
    protected function collectUrls(): array
    {
        $base = rtrim(config('app.frontend_url'), '/');

        $entries = collect([['loc' => $base.'/', 'lastmod' => null]]);

        $entries = $entries->merge(
            Page::published()->where('type', 'page')->where('no_index', false)->get(['slug', 'updated_at'])
                ->map(fn (Page $page) => $this->entry($base.'/'.$page->slug, $page->updated_at))
        );

        $entries = $entries->merge(
            Product::active()->get(['slug', 'updated_at'])
                ->map(fn (Product $product) => $this->entry($base.'/products/'.$product->slug, $product->updated_at))
        );

        $entries = $entries->merge(
            ProductCategory::where('status', 'active')->get(['slug', 'updated_at'])
                ->map(fn (ProductCategory $category) => $this->entry($base.'/products/category/'.$category->slug, $category->updated_at))
        );

        $entries = $entries->merge(
            PostCategory::where('status', 'active')->get(['slug', 'updated_at'])
                ->map(fn (PostCategory $category) => $this->entry($base.'/blog/category/'.$category->slug, $category->updated_at))
        );

        $entries = $entries->merge(
            Post::published()->get(['slug', 'updated_at'])
                ->map(fn (Post $post) => $this->entry($base.'/blog/'.$post->slug, $post->updated_at))
        );

        return $entries->all();
    }

    /**
     * @return array{loc: string, lastmod: ?string}
     */
    protected function entry(string $loc, mixed $updatedAt): array
    {
        return ['loc' => $loc, 'lastmod' => $updatedAt?->toAtomString()];
    }

    /**
     * @param  array<int, array{loc: string, lastmod: ?string}>  $urls
     */
    protected function buildXml(array $urls): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        foreach ($urls as $entry) {
            $url = $xml->addChild('url');
            $url->addChild('loc', htmlspecialchars($entry['loc'], ENT_XML1));

            if ($entry['lastmod']) {
                $url->addChild('lastmod', $entry['lastmod']);
            }
        }

        return $xml->asXML();
    }

    public function render()
    {
        return view('livewire.admin.advance.sitemap')->layout('layouts.admin', ['title' => 'Sitemap']);
    }
}
