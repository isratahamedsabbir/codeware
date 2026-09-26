<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\ContentCache;
use App\Support\Seo\SeoResolver;

/**
 * What the storefront actually puts in <head>.
 *
 * Every other SEO test in the suite checks a *write* — a form filling the
 * settings table, an admin form hydrating a Page row, a button writing a file to
 * disk. None of them look at a rendered response, which is why a storefront page
 * could go out with no canonical and no og:type at all without anything failing.
 *
 * These go through the real middleware stack and assert the tags a crawler reads.
 */
beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');
    SeoResolver::flush();
});

afterEach(function () {
    SeoResolver::flush();
});

/**
 * The value of one <meta name|property="..."> on the response, or null.
 */
function metaOf($response, string $attribute, string $value): ?string
{
    if (preg_match('/<meta\s+'.$attribute.'="'.preg_quote($value, '/').'"\s+content="([^"]*)"/', $response->getContent(), $m)
        || preg_match('/<meta\s+content="([^"]*)"\s+'.$attribute.'="'.preg_quote($value, '/').'"\s*\/?>/', $response->getContent(), $m)) {
        return html_entity_decode($m[1], ENT_QUOTES);
    }

    return null;
}

/*
|--------------------------------------------------------------------------
| og:type
|--------------------------------------------------------------------------
|
| It used to be the literal string "website" on every page, so a product link
| and a blog post link unfurled as if they were homepages. That is the one tag a
| rich result is read from.
|
*/

/**
 * A product's paired Page, which is where its slug and SEO actually live. The
 * cascade is driven by the admin forms rather than the model, so a factory-built
 * product has no page until one is made here.
 */
function aProductPage(string $title = 'A fine product'): Page
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

function aPostPage(string $title = 'A fine post'): Page
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

it('declares a product og:type on a product page', function () {
    $this->get('/products/'.aProductPage()->slug)
        ->assertOk()
        ->assertSee('property="og:type" content="product"', escape: false);
});

it('declares an article og:type on a blog post, and website elsewhere', function () {
    $this->get('/blog/'.aPostPage()->slug)
        ->assertOk()
        ->assertSee('property="og:type" content="article"', escape: false);

    $this->get('/')
        ->assertOk()
        ->assertSee('property="og:type" content="website"', escape: false);
});

/*
|--------------------------------------------------------------------------
| Indexability
|--------------------------------------------------------------------------
|
| A cart, a checkout, an order confirmation, a favourites list and the whole
| customer account area were all indexable: the robots tag was only ever emitted
| when a Page had no_index set, and those routes pass no Page at all — so each
| one shipped a self-referential canonical and an "index, follow" invitation.
|
*/

it('refuses to index a visitor own state', function (string $uri) {
    $this->actingAs(User::factory()->create());

    $this->get($uri)->assertOk()->assertSee('name="robots" content="noindex"', escape: false);
})->with([
    'the cart' => '/cart',
    'the checkout' => '/checkout',
    'the favourites list' => '/favorites',
    'the account dashboard' => '/account',
    'the order history' => '/account/orders',
    'the profile' => '/account/profile',
]);

it('indexes the pages a visitor would want found, without a pointless robots tag', function (string $uri) {
    // An absent robots tag already means index,follow; writing that out on every
    // page is noise, so its absence is the assertion.
    $this->get($uri)->assertOk()->assertDontSee('name="robots"', escape: false);
})->with([
    'the home page' => '/',
    'the shop' => '/shop',
    'the blog' => '/blog',
]);

/*
|--------------------------------------------------------------------------
| The fallback chain
|--------------------------------------------------------------------------
*/

it('uses a page own seo title as the whole title', function () {
    // No site name appended: an admin who typed a 58-character SEO title meant
    // it literally, and the suffix would push it past what fits in a SERP.
    Setting::set('site_name', 'Codeware');

    Page::factory()->published()->create([
        'title' => ['en' => 'About us', 'bn' => ''],
        'slug' => 'about',
        'seo_title' => 'About Codeware',
    ]);

    $this->get('/about')
        ->assertOk()
        ->assertSee('<title>About Codeware</title>', escape: false)
        ->assertDontSee('<title>About Codeware | Codeware', escape: false);
});

it('builds a title from the admin template when the page has no seo title', function () {
    Setting::set('site_name', 'Codeware');
    Setting::set('seo_title_template', '%s :: Codeware');

    Page::factory()->published()->create(['title' => ['en' => 'About us', 'bn' => ''], 'slug' => 'about']);

    $this->get('/about')
        ->assertOk()
        ->assertSee('<title>About us :: Codeware</title>', escape: false);
});

it('still puts the page name in when the title template has no placeholder', function () {
    // A template without %s would throw the page name away entirely, which is a
    // typo rather than an intent — so the name is appended regardless.
    Setting::set('site_name', 'Codeware');
    Setting::set('seo_title_template', 'Codeware');

    Page::factory()->published()->create(['title' => ['en' => 'About us', 'bn' => ''], 'slug' => 'about']);

    $this->get('/about')
        ->assertOk()
        ->assertSee('<title>About us Codeware</title>', escape: false);
});

it('lets a page seo title win over the global meta title', function () {
    Setting::set('seo_meta_title', 'Global Title');
    Setting::set('site_name', 'Codeware');

    Page::factory()->published()->create([
        'title' => ['en' => 'About us', 'bn' => ''],
        'slug' => 'about',
        'seo_title' => 'About Codeware | Who we are',
    ]);

    $this->get('/about')
        ->assertOk()
        ->assertSee('<title>About Codeware | Who we are</title>', escape: false)
        ->assertDontSee('<title>About us', escape: false);
});

it('falls back through global settings to the site description', function () {
    Setting::set('site_name', 'Codeware');
    Setting::set('site_description', 'Premium web solutions built with Codeware.');

    $this->get('/shop')
        ->assertOk()
        ->assertSee('name="description" content="Premium web solutions built with Codeware."', escape: false);
});

it('serves the global seo copy in the requested language', function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    Setting::set('site_name', 'Codeware');
    Setting::set('seo_meta_description', json_encode([
        'en' => 'Buy fresh produce online.',
        'bn' => 'তাজা কৃষি পণ্য কিনুন।',
    ]));

    $english = $this->get('/shop');
    $bengali = $this->get('/shop?lang=bn');

    expect(metaOf($english, 'name', 'description'))->toBe('Buy fresh produce online.')
        ->and(metaOf($bengali, 'name', 'description'))->toBe('তাজা কৃষি পণ্য কিনুন।');
});

it('falls back to the primary language copy when a translation was left blank', function () {
    Setting::set('site_name', 'Codeware');
    Setting::set('seo_meta_description', json_encode([
        'en' => 'Buy fresh produce online.',
        'bn' => '',
    ]));

    expect(metaOf($this->get('/shop?lang=bn'), 'name', 'description'))->toBe('Buy fresh produce online.');
});

/*
|--------------------------------------------------------------------------
| The Global SEO screen and the home page
|--------------------------------------------------------------------------
|
| The site root is the one page the Global SEO title is the answer for, rather
| than a fallback below a templated page name — appending "| Site Name" to a
| title the admin typed for the whole site just repeats the site name at them.
| And it has to be true of every theme, not the storefront one: the "default"
| theme renders through the auth layout, which used to emit a <title> and
| nothing else, so its home page shipped with no description, no og:*, no
| twitter:* and no canonical at all.
|
*/

/**
 * Every theme's home page, as the head a crawler reads it.
 *
 * @return array{title: string|null, description: string|null, og: string|null, twitter: string|null, canonical: string|null}
 */
function homeHead(string $theme): array
{
    Setting::set('site_theme', $theme);
    SeoResolver::flush();

    $html = test()->get('/')->getContent();

    preg_match('/<title>(.*?)<\/title>/s', $html, $title);

    return [
        'title' => html_entity_decode(trim($title[1] ?? ''), ENT_QUOTES) ?: null,
        'description' => metaOf(test()->get('/'), 'name', 'description'),
        'og' => metaOf(test()->get('/'), 'property', 'og:title'),
        'twitter' => metaOf(test()->get('/'), 'name', 'twitter:title'),
        'canonical' => str_contains($html, 'rel="canonical"') ? 'present' : null,
    ];
}

it('uses the global seo title verbatim on the home page, in every theme', function (string $theme) {
    Setting::set('site_name', 'Codeware');
    Setting::set('seo_title_template', '%s | Codeware');
    Setting::set('seo_meta_title', 'Codeware — the whole site in one line');
    Setting::set('seo_og_title', 'Codeware unfurls like this');
    Setting::set('seo_twitter_title', 'Codeware on Twitter');
    Setting::set('seo_meta_description', 'One description for the whole site.');

    expect(homeHead($theme))->toBe([
        'title' => 'Codeware — the whole site in one line',
        'description' => 'One description for the whole site.',
        'og' => 'Codeware unfurls like this',
        'twitter' => 'Codeware on Twitter',
        'canonical' => 'present',
    ]);
})->with(['default', 'ecommerce', 'portfolio']);

it('still lets the home page override the global seo title', function () {
    Setting::set('site_name', 'Codeware');
    Setting::set('seo_meta_title', 'The global title');

    $home = Page::create([
        'type' => 'home',
        'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'Home'],
        'seo_title' => ['en' => 'This home page is ours'],
        'status' => 'active',
    ]);

    expect(homeHead('ecommerce')['title'])->toBe('This home page is ours');

    $home->update(['seo_title' => []]);
    ContentCache::bust();

    expect(homeHead('ecommerce')['title'])->toBe('The global title');
});

it('falls back to a templated home title when no global one is set', function () {
    Setting::set('site_name', 'Codeware');
    Setting::set('seo_title_template', '%s | Codeware');

    Page::create([
        'type' => 'home',
        'slug' => 'home',
        'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'Welcome'],
        'status' => 'active',
    ]);
    ContentCache::bust();

    expect(homeHead('ecommerce')['title'])->toBe('Welcome | Codeware');
});

it('makes an og image absolute, since a relative one is dropped by every scraper', function () {
    Setting::set('site_name', 'Codeware');
    Setting::set('seo_og_image', '/storage/media/og.png');

    $this->get('/')
        ->assertOk()
        ->assertSee('property="og:image" content="'.url('/storage/media/og.png').'"', escape: false);
});

/*
|--------------------------------------------------------------------------
| Canonical
|--------------------------------------------------------------------------
*/

it('points the canonical at the configured site url, without the tracking params the visitor arrived with', function () {
    Setting::set('seo_site_url', 'https://codeware.test/');

    $this->get('https://codeware.test/shop?utm_source=facebook')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://codeware.test/shop">', escape: false);
});

it('normalises a messy site url down to scheme, host and path', function () {
    // An admin pasting "HTTPS://Codeware.test:443/" into the setting should get
    // the same canonical as one who typed it correctly, and should not have
    // their visitors bounced off an uppercase-scheme redirect for nothing.
    Setting::set('seo_site_url', 'HTTPS://Codeware.test:443/');

    $this->get('https://codeware.test/shop')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://codeware.test/shop">', escape: false);
});

it('honours a page own canonical base and slug', function () {
    Page::factory()->published()->create([
        'title' => ['en' => 'About us', 'bn' => ''],
        'slug' => 'about',
        'canonical_base' => 'https://codeware.test',
        'canonical_slug' => 'company/about',
    ]);

    $this->get('/about')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://codeware.test/company/about">', escape: false);
});

/*
|--------------------------------------------------------------------------
| One address, actually enforced
|--------------------------------------------------------------------------
|
| A canonical tag is a claim. Until the site stops serving three spellings of
| itself, the claim is only half true and the ranking signal stays split.
|
*/

it('301s a visitor who arrived on the wrong spelling of the site address', function (string $from, string $to) {
    Setting::set('seo_site_url', 'https://codeware.test');

    $this->get($from)->assertStatus(301)->assertRedirect($to);
})->with([
    'the www variant' => ['http://www.codeware.test/shop', 'https://codeware.test/shop'],
    'the http variant' => ['http://codeware.test/shop', 'https://codeware.test/shop'],
]);

it('carries a campaign query string across that redirect, so the link still lands', function () {
    Setting::set('seo_site_url', 'https://codeware.test');

    $this->get('http://codeware.test/shop?utm_source=facebook')
        ->assertStatus(301)
        ->assertRedirect('https://codeware.test/shop?utm_source=facebook');
});

it('leaves the other applications on their own hosts alone', function () {
    // The admin panel, vendor portal and delivery portal are separate apps with
    // their own logins; redirecting them onto the storefront host breaks them.
    // Reaching the login page at all is the proof no canonical redirect ran.
    Setting::set('seo_site_url', 'https://codeware.test');

    $this->get('http://admin.codeware.test/products')
        ->assertRedirect(route('login'));
});

it('settles on an answer for the site root instead of redirecting in a circle', function (string $from) {
    // The homepage is the one URL that arrives spelled two ways — with and
    // without its slash — and it is the one every visitor types. Comparing the
    // two addresses as written sends the homepage into a 301 loop that never
    // lands, which is a site-wide outage on the front door rather than a
    // canonical nicety. Either spelling has to be a 200.
    Setting::set('seo_site_url', 'https://codeware.test');

    $this->get($from)->assertOk();
})->with([
    'with its slash' => ['https://codeware.test/'],
    'without its slash' => ['https://codeware.test'],
]);

it('does not redirect at all while no site url is configured', function () {
    // A local install is served over http on a different host than production, so
    // an unset seo_site_url has to mean "no opinion" rather than "redirect to
    // nowhere".
    $this->get('/shop')->assertOk();
});
