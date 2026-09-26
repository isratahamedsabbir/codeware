<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;
use App\Support\Seo\SeoResolver;
use App\Support\Seo\Sitemap;
use Illuminate\Support\Facades\Cache;

/**
 * What a crawler is actually handed at /sitemap.xml and /robots.txt.
 *
 * Both used to be files in public/ that an admin wrote by hand, which meant the
 * suite could assert a file had been written but never that what a crawler reads
 * was right. It was not: the addresses came from config('app.frontend_url') — the
 * Next.js dev server — and a good share of the paths were routes this
 * application does not have, so the file advertised 404s as inventory.
 */
beforeEach(function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);

    Setting::set('site_theme', 'ecommerce');
    Setting::set('site_name', 'Codeware');
    Setting::set('seo_site_url', 'https://codeware.test');

    SeoResolver::flush();
});

afterEach(function () {
    Cache::flush();
});

function aSitemapPage(string $type, string $slug, array $extra = []): Page
{
    return Page::create(array_merge([
        'type' => $type,
        'user_id' => User::factory()->create()->id,
        'title' => ['en' => $slug],
        'slug' => $slug,
        'status' => 'active',
    ], $extra));
}

function sitemapEntries(): array
{
    return Sitemap::entries();
}

/*
|--------------------------------------------------------------------------
| The addresses themselves
|--------------------------------------------------------------------------
*/

it('advertises the site its own address, not the frontend dev server', function () {
    // The one thing the old file got structurally wrong: every URL in it was
    // built from config('app.frontend_url'), which is the Next.js dev server, so
    // the sitemap pointed crawlers at a host the site has never claimed.
    config(['app.frontend_url' => 'http://localhost:3000']);

    $xml = $this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('<loc>https://codeware.test/</loc>')
        ->and($xml)->not->toContain('localhost:3000');
});

it('serves the sitemap as xml, at the address a crawler looks for it', function () {
    $response = $this->get('https://codeware.test/sitemap.xml')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/xml')
        ->and($response->getContent())->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->and($response->getContent())->toContain('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"');

    // Parses as XML rather than merely looking like it.
    $parsed = simplexml_load_string($response->getContent());
    expect($parsed)->not->toBeFalse()
        ->and($parsed->getName())->toBe('urlset');
});

it('lists the home page exactly once, at one spelling', function () {
    // /home is an alias of / and a page the site also lists by slug. Three
    // addresses, one page, and a crawler free to pick any of them.
    aSitemapPage('page', 'home');

    $locs = array_map(fn ($entry) => $entry['loc'], Sitemap::entries());

    expect(array_count_values($locs)['https://codeware.test/'] ?? 0)->toBe(1)
        ->and($locs)->not->toContain('https://codeware.test/home');
});

/*
|--------------------------------------------------------------------------
| What belongs in it
|--------------------------------------------------------------------------
*/

it('lists published products at the routes the storefront really serves', function () {
    $product = Product::factory()->published()->create();
    aSitemapPage('product', 'widget', ['product_id' => $product->id]);

    // /products/category/{slug} and /blog/category/{slug} are not routes this
    // application has; the old sitemap listed both anyway.
    expect($this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent())
        ->toContain('<loc>https://codeware.test/products/widget</loc>')
        ->not->toContain('products/category/')
        ->not->toContain('blog/category/');
});

it('leaves out a product whose page an admin marked no_index', function () {
    $product = Product::factory()->published()->create();
    aSitemapPage('product', 'hidden-widget', ['product_id' => $product->id, 'no_index' => true]);

    // A sitemap that invites a crawler to a noindex URL is asking for the two
    // signals to be believed equally, and Google believes the lower of the two.
    expect($this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent())
        ->not->toContain('hidden-widget');
});

it('leaves out a draft and an unpublished product alike', function () {
    $draft = Product::factory()->create(['status' => 'draft']);
    aSitemapPage('product', 'draft-widget', ['product_id' => $draft->id]);

    expect($this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent())
        ->not->toContain('draft-widget');
});

it('leaves out a product with no paired page, because it has no address', function () {
    // Nothing to serve it at yet, so there is no URL to advertise. Guessing one
    // from the product name would put a 404 in the inventory.
    Product::factory()->published()->create();

    expect($this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent())
        ->not->toContain('/products/');
});

it('lists published posts and categories at their real paths', function () {
    $post = Post::factory()->published()->create();
    aSitemapPage('post', 'a-post', ['post_id' => $post->id]);

    $category = ProductCategory::create(['name' => ['en' => 'Tools'], 'status' => 'active']);
    aSitemapPage('product_category', 'tools', ['category_id' => $category->id]);

    $xml = $this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('<loc>https://codeware.test/blog/a-post</loc>')
        ->and($xml)->toContain('<loc>https://codeware.test/category/tools</loc>');
});

/*
|--------------------------------------------------------------------------
| Only what this site can serve
|--------------------------------------------------------------------------
*/

it('advertises no products on a theme that has no product pages', function () {
    // The same application is a portfolio under one theme and a shop under
    // another. A sitemap that kept listing products through a portfolio would be
    // a page of 404s, and a crawler that reads it learns the site lies.
    $product = Product::factory()->published()->create();
    aSitemapPage('product', 'widget', ['product_id' => $product->id]);

    Setting::set('site_theme', 'portfolio');
    SeoResolver::flush();

    $xml = $this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('<loc>https://codeware.test/</loc>')
        ->and($xml)->not->toContain('products/widget')
        ->and($xml)->not->toContain('/shop');
});

it('still answers on a theme with no storefront templates of its own', function () {
    Setting::set('site_theme', 'portfolio');

    // The route is registered outside the theme group on purpose: a crawler
    // asking what this site contains should not get a 404 because the site
    // happens to be showing a portfolio today.
    $this->get('https://codeware.test/sitemap.xml')->assertOk();
    $this->get('https://codeware.test/robots.txt')->assertOk();
});

/*
|--------------------------------------------------------------------------
| Never stale
|--------------------------------------------------------------------------
*/

it('shows a product published a moment ago without anybody pressing a button', function () {
    $before = $this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent();
    expect($before)->not->toContain('late-widget');

    $product = Product::factory()->published()->create();
    aSitemapPage('product', 'late-widget', ['product_id' => $product->id]);

    // The cache is keyed on the content's own fingerprint, so a write produces a
    // new key and the next request rebuilds. Nothing observes the models and
    // nothing has to be remembered.
    expect($this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent())
        ->toContain('late-widget');
});

it('reports the date a page was last changed, not the date the sitemap was built', function () {
    // forceFill, not update(): updated_at is guarded off the fillable set, so a
    // mass-assigned timestamp is discarded in silence and the test would pass
    // against a date nothing ever wrote.
    $product = Product::factory()->published()->create();
    $product->forceFill(['updated_at' => '2026-03-01 09:00:00'])->saveQuietly();

    $page = aSitemapPage('product', 'widget', ['product_id' => $product->id]);
    $page->forceFill(['updated_at' => '2026-03-04 10:00:00'])->saveQuietly();

    // Either row can be edited without the other, so lastmod is the later of the
    // two edits — a stale product timestamp must not date a page back.
    $xml = $this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toMatch('/<lastmod>2026-03-04T10:00:00[+\-]\d\d:\d\d<\/lastmod>/')
        ->and($xml)->not->toContain('2026-03-01');
});

it('stamps a lastmod that the sitemap can still be read for', function () {
    // The regression this file exists for: an instanceof check narrower than the
    // Carbon class this application actually casts to does not fail, it silently
    // omits lastmod from every entry and leaves a crawler guessing at freshness.
    $product = Product::factory()->published()->create();
    aSitemapPage('product', 'widget', ['product_id' => $product->id]);

    $xml = $this->get('https://codeware.test/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('<lastmod>');
});

/*
|--------------------------------------------------------------------------
| robots.txt
|--------------------------------------------------------------------------
*/

it('points every crawler at the sitemap, whatever the admin has typed', function () {
    Setting::set('seo_robots_txt', "User-agent: *\nDisallow: /admin");

    $body = $this->get('https://codeware.test/robots.txt')->assertOk()->getContent();

    expect($body)->toBe("User-agent: *\nDisallow: /admin\n\nSitemap: https://codeware.test/sitemap.xml\n");
});

it('reads as plain text, which is the only format a crawler parses here', function () {
    $this->get('https://codeware.test/robots.txt')->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

it('makes no opinion rather than an empty file when nothing is configured', function () {
    // A stored blank would otherwise be a site-wide deindex sitting in a box
    // somebody can press Save on. "User-agent: * / Disallow:" is what a missing
    // robots.txt means.
    $body = $this->get('https://codeware.test/robots.txt')->assertOk()->getContent();

    expect($body)->toBe("User-agent: *\nDisallow:\n\nSitemap: https://codeware.test/sitemap.xml\n");
});
