<?php

use App\Models\Language;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Support\Seo\SeoResolver;

/**
 * The schema.org JSON-LD a page ships.
 *
 * A graph is asserted by decoding it and reading nodes by @type and @id, rather
 * than by matching the script tag's text. The output is a machine format, and a
 * test that string-matches a JSON blob breaks on the first key reordering while
 * saying nothing about whether the graph is correct.
 */
beforeEach(function () {
    // Created per test rather than assumed: Locale::isSupported() reads the
    // languages table, and without a Bengali row the ?lang=bn switch below is
    // silently ignored rather than honoured.
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    Setting::set('site_theme', 'ecommerce');
    Setting::set('site_name', 'Codeware');
    Setting::set('currency_code', 'BDT');

    // Pinned rather than inherited: Setting::get is cached for the life of the
    // process, so a site_url left behind by an earlier test would silently
    // rewrite every URL these assertions are about.
    Setting::set('seo_site_url', 'https://codeware.test');

    SeoResolver::flush();
});

afterEach(function () {
    SeoResolver::flush();
});

/**
 * The decoded @graph on the response, or null when the page ships none.
 *
 * @return array<int, array<string, mixed>>|null
 */
function graphOf($response): ?array
{
    if (! preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $m)) {
        return null;
    }

    $decoded = json_decode($m[1], true);

    expect($decoded)->toBeArray()
        ->and($decoded['@context'])->toBe('https://schema.org');

    return $decoded['@graph'];
}

/**
 * The one node of a given type, failing the test if there isn't exactly one.
 *
 * @return array<string, mixed>
 */
function nodeOfType(?array $graph, string $type): array
{
    $matches = array_values(array_filter($graph ?? [], fn ($node) => ($node['@type'] ?? null) === $type));

    expect($matches)->toHaveCount(1, "expected exactly one {$type} node");

    return $matches[0];
}

function aProductDetailPage(string $title = 'A fine product'): Page
{
    $product = Product::factory()->published()->create();

    return Page::create([
        'type' => 'product',
        'product_id' => $product->id,
        'user_id' => User::factory()->create()->id,
        'title' => ['en' => $title],
        'status' => 'active',
    ]);
}

function aPostDetailPage(string $title = 'A fine post'): Page
{
    $post = Post::factory()->published()->create();

    return Page::create([
        'type' => 'post',
        'post_id' => $post->id,
        'user_id' => User::factory()->create()->id,
        'title' => ['en' => $title],
        'status' => 'active',
    ]);
}

/*
|--------------------------------------------------------------------------
| The site itself
|--------------------------------------------------------------------------
*/

it('describes the site, once, and cross-references it from the page', function () {
    // The organisation, the site and the page are three separate nodes that have
    // to agree; without @ids a crawler sees a fresh unlinked Organization on
    // every page and has to match on strings to work out who published what.
    $graph = graphOf($this->get('https://codeware.test/')->assertOk());

    $organization = nodeOfType($graph, 'Organization');
    $website = nodeOfType($graph, 'WebSite');
    $webPage = nodeOfType($graph, 'WebPage');

    expect($organization['@id'])->toBe('https://codeware.test/#organization')
        ->and($organization['name'])->toBe('Codeware')
        ->and($website['publisher'])->toBe(['@id' => 'https://codeware.test/#organization'])
        ->and($webPage['isPartOf'])->toBe(['@id' => 'https://codeware.test/#website'])
        ->and($webPage['url'])->toBe('https://codeware.test/')
        ->and($webPage['inLanguage'])->toBe('en');
});

/*
|--------------------------------------------------------------------------
| Products
|--------------------------------------------------------------------------
*/

it('describes a product with the price the page actually shows', function () {
    $product = aProductDetailPage()->product;
    $product->update(['price' => 1200, 'discount_price' => 950, 'sku' => 'CW-950']);

    $graph = graphOf($this->get('https://codeware.test/products/'.$product->page->slug)->assertOk());

    $node = nodeOfType($graph, 'Product');

    // The discount price, not the struck-through one: structured data quoting a
    // price the page doesn't show is how a shop earns a "price is wrong" report.
    expect($node['offers']['price'])->toBe(950)
        ->and($node['offers']['priceCurrency'])->toBe('BDT')
        ->and($node['offers']['url'])->toBe($node['url'])
        ->and($node['sku'])->toBe('CW-950');
});

it('marks a product in stock or out of stock from its quantity', function () {
    $page = aProductDetailPage();
    $page->product->update(['price' => 100, 'quantity' => 4]);

    $inStock = nodeOfType(graphOf($this->get('https://codeware.test/products/'.$page->slug)->assertOk()), 'Product');
    expect($inStock['offers']['availability'])->toBe('https://schema.org/InStock');

    SeoResolver::flush();
    $page->product->update(['quantity' => 0]);

    $outOfStock = nodeOfType(graphOf($this->get('https://codeware.test/products/'.$page->slug)->assertOk()), 'Product');
    expect($outOfStock['offers']['availability'])->toBe('https://schema.org/OutOfStock');
});

it('offers no price at all for an upcoming product', function () {
    $page = aProductDetailPage();
    $page->product->update(['price' => 0, 'is_upcoming' => true]);

    $node = nodeOfType(graphOf($this->get('https://codeware.test/products/'.$page->slug)->assertOk()), 'Product');

    // A 0.00 offer in the SERP for something that cannot be bought is worse than
    // no offer at all.
    expect($node)->not->toHaveKey('offers')
        ->and($node['availability'])->toBe('https://schema.org/PreOrder');
});

it('averages only the approved reviews into a star rating', function () {
    $page = aProductDetailPage();
    $product = $page->product;

    foreach ([[5, 'approved'], [4, 'approved'], [1, 'pending'], [2, 'rejected']] as [$rating, $status]) {
        Review::create([
            'reviewable_type' => $product->getMorphClass(),
            'reviewable_id' => $product->id,
            'rating' => $rating,
            'body' => 'A review',
            'status' => $status,
        ]);
    }

    $node = nodeOfType(graphOf($this->get('https://codeware.test/products/'.$page->slug)->assertOk()), 'Product');

    expect($node['aggregateRating']['reviewCount'])->toBe(2)
        ->and($node['aggregateRating']['ratingValue'])->toBe(4.5)
        ->and($node['aggregateRating']['bestRating'])->toBe(5);
});

it('claims no rating at all when there is nothing approved to rate', function () {
    $page = aProductDetailPage();

    Review::create([
        'reviewable_type' => $page->product->getMorphClass(),
        'reviewable_id' => $page->product->id,
        'rating' => 5,
        'body' => 'Waiting for moderation',
        'status' => 'pending',
    ]);

    // A rating with a reviewCount of 0 is a rejected rich result, and it hides
    // the very reviews it was meant to surface.
    $node = nodeOfType(graphOf($this->get('https://codeware.test/products/'.$page->slug)->assertOk()), 'Product');

    expect($node)->not->toHaveKey('aggregateRating');
});

it('lists a product gallery as absolute image urls, featured image first', function () {
    $page = aProductDetailPage();
    $product = $page->product;
    $product->update(['featured_image' => '/storage/featured.png']);

    $media = MediaLibrary::factory()->count(2)->create();
    foreach ($media as $item) {
        $product->gallery()->attach($item, ['sort_order' => 0]);
    }

    $node = nodeOfType(graphOf($this->get('https://codeware.test/products/'.$page->slug)->assertOk()), 'Product');

    // Read back off the model rather than hardcoded: what makes a URL valid in
    // a Product node is that it is absolute, not which disk wrote it or which
    // scheme that disk was configured with.
    expect($node['image'][0])->toBe('https://codeware.test/storage/featured.png')
        ->and(array_slice($node['image'], 1))->toEqualCanonicalizing(
            $media->map(fn ($item) => $item->url)->all()
        );

    foreach ($node['image'] as $image) {
        expect($image)->toMatch('#^https?://#');
    }
});

/*
|--------------------------------------------------------------------------
| Articles
|--------------------------------------------------------------------------
*/

it('describes a blog post as a BlogPosting with its dates and author', function () {
    $page = aPostDetailPage();
    $post = $page->post;
    $post->update([
        'published_at' => '2026-01-15 10:00:00',
        'content' => ['en' => str_repeat('word ', 300)],
    ]);

    $node = nodeOfType(graphOf($this->get('https://codeware.test/blog/'.$page->slug)->assertOk()), 'BlogPosting');

    // The post's own title, not the paired Page's: that is the headline the
    // theme puts in its <h1>, and a headline the page never shows is a headline
    // Google is entitled to ignore.
    expect($node['headline'])->toBe($post->title)
        ->and($node['datePublished'])->toStartWith('2026-01-15')
        ->and($node['dateModified'])->not->toBeEmpty()
        ->and($node['author']['name'])->toBe($post->user->name)
        ->and($node['publisher'])->toBe(['@id' => 'https://codeware.test/#organization'])
        ->and($node['mainEntityOfPage'])->toBe(['@id' => $node['url'].'#webpage']);
});

/*
|--------------------------------------------------------------------------
| Breadcrumbs
|--------------------------------------------------------------------------
*/

it('describes a product breadcrumb through its real ancestors only', function () {
    $page = aProductDetailPage();
    $page->product->update(['price' => 100]);

    $node = nodeOfType(graphOf($this->get('https://codeware.test/products/'.$page->slug)->assertOk()), 'BreadcrumbList');

    expect($node['itemListElement'])->toHaveCount(3)
        ->and($node['itemListElement'][0])->toMatchArray(['position' => 1, 'name' => 'Codeware', 'item' => 'https://codeware.test/'])
        ->and($node['itemListElement'][1]['name'])->toBe('Shop')
        // The product's own name, which is what the theme puts in its <h1>.
        ->and($node['itemListElement'][2]['name'])->toBe($page->product->name);
});

it('gives a plain page a one-step trail, home then page', function () {
    // A breadcrumb that invents a level to look deeper is treated as a soft
    // cloaking attempt, so a page with no hierarchy above it gets no fake one.
    Page::factory()->published()->create(['title' => ['en' => 'About us', 'bn' => ''], 'slug' => 'about']);

    $node = nodeOfType(graphOf($this->get('https://codeware.test/about')->assertOk()), 'BreadcrumbList');

    expect($node['itemListElement'])->toHaveCount(2)
        ->and($node['itemListElement'][1]['name'])->toBe('About us');
});

/*
|--------------------------------------------------------------------------
| Where it must not appear
|--------------------------------------------------------------------------
*/

it('ships no structured data on a page that is not indexable', function () {
    // It could never be read there, and a customer's cart is the last place to
    // be describing products and prices to a crawler.
    expect(graphOf($this->get('https://codeware.test/cart')->assertOk()))->toBeNull();
});

it('never writes a null field, because a null is a claim of knowing nothing', function () {
    $page = aProductDetailPage();
    $page->product->update(['price' => 100, 'sku' => null, 'featured_image' => null]);

    $product = nodeOfType(graphOf($this->get('https://codeware.test/products/'.$page->slug)->assertOk()), 'Product');

    $walk = function (mixed $value) use (&$walk): bool {
        if (is_array($value)) {
            return array_all($value, fn ($item) => $item !== null && $walk($item));
        }

        return true;
    };

    expect($walk($product))->toBeTrue()
        ->and($product)->not->toHaveKey('sku')
        ->and($product)->not->toHaveKey('image');
});

it('follows the active locale into the structured data', function () {
    // Driven through ?lang=, the way a real visitor switches — not by poking
    // the app's locale, which would skip the middleware that sets it.
    Setting::set('site_name', 'কোডওয়্যার');

    Page::factory()->published()->create([
        'title' => ['en' => 'About us', 'bn' => 'আমাদের সম্পর্কে'],
        'slug' => 'about',
    ]);

    $response = $this->get('https://codeware.test/about?lang=bn')->assertOk();
    $graph = graphOf($response);

    expect(nodeOfType($graph, 'Organization')['name'])->toBe('কোডওয়্যার')
        ->and(nodeOfType($graph, 'BreadcrumbList')['itemListElement'][1]['name'])->toBe('আমাদের সম্পর্কে')
        ->and(nodeOfType($graph, 'WebPage')['inLanguage'])->toBe('bn');
});
