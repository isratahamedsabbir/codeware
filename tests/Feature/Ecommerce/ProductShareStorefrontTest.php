<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

function shareableProduct(string $slug, array $attributes = [], array $pageAttributes = []): Product
{
    $product = Product::factory()->published()->create($attributes + ['sort_order' => 0]);

    pairPageFor($product, 'product', $slug, User::factory()->create()->id)
        ->update($pageAttributes);

    return $product;
}

/**
 * The href of every share link in the row, keyed by network, decoded back into
 * a query map so a test can assert on a parameter instead of on the exact
 * encoding of the whole URL.
 *
 * @return array<string, array<string, string>>
 */
function shareQueryMap(string $html): array
{
    preg_match_all('/href="(https?:\/\/[^"]*|mailto:[^"]*)"/', $html, $matches);

    $out = [];

    foreach ($matches[1] as $href) {
        $href = html_entity_decode($href, ENT_QUOTES);

        if (str_starts_with($href, 'mailto:')) {
            parse_str(substr($href, strlen('mailto:?')), $params);
        } else {
            parse_str((string) parse_url($href, PHP_URL_QUERY), $params);
        }

        $host = (string) parse_url($href, PHP_URL_HOST);
        $out[$host] = array_map('strval', $params);
    }

    return $out;
}

it('offers every share network on the product page', function () {
    shareableProduct('share-tea', ['name' => ['en' => 'Share Tea', 'bn' => '']]);

    $html = $this->get('/products/share-tea')->assertOk()->getContent();

    $query = shareQueryMap($html);

    expect($query)->toHaveKeys([
        'www.facebook.com',
        'twitter.com',
        'wa.me',
        't.me',
        'www.linkedin.com',
        'www.pinterest.com',
    ])
        // mailto: has no host, so it is matched by scheme rather than by key.
        ->and($html)->toContain('mailto:?');

    foreach (['Facebook', 'X / Twitter', 'WhatsApp', 'Telegram', 'LinkedIn', 'Pinterest', 'Email'] as $label) {
        expect($html)->toContain(__('Share on :network', ['network' => $label]));
    }

    expect($html)->toContain(__('Copy link'));
});

it('shares the page canonical rather than a url built from the slug', function () {
    $product = shareableProduct(
        'canonical-tea',
        ['name' => ['en' => 'Canonical Tea', 'bn' => '']],
        // An admin-set canonical wins over the request URL, so this is the one
        // case where a share link built from the request would be wrong.
        ['canonical_base' => 'https://shop.example.com', 'canonical_slug' => 'tea-of-the-month'],
    );

    $html = $this->get('/products/canonical-tea')->assertOk()->getContent();

    expect($product->exists)->toBeTrue()
        // og:url is emitted from the same SeoData, so if these two agree the
        // share row and the page are describing the same URL.
        ->and($html)->toContain('<meta property="og:url" content="https://shop.example.com/tea-of-the-month">')
        ->and($html)->toContain(rawurlencode('https://shop.example.com/tea-of-the-month'));
});

it('percent-encodes the shared text so reserved characters cannot split the share link', function () {
    // A share link is a URL with the product title inside it, and titles
    // contain & and ? routinely. Unencoded, the & ends the text parameter and
    // the rest of the title arrives to the network as separate parameters.
    Setting::set('seo_title_template', '%s');

    shareableProduct('fish-tea', ['name' => ['en' => 'Fish & Chips? Yes', 'bn' => '']]);

    $html = $this->get('/products/fish-tea')->assertOk()->getContent();

    $query = shareQueryMap($html);

    // Round-trips as exactly the one title, with nothing spilled into a second
    // parameter and no stray "yes" key.
    expect($query['twitter.com']['text'])->toBe('Fish & Chips? Yes')
        ->and($query['twitter.com'])->not->toHaveKey('yes')
        ->and($query['t.me']['text'])->toBe('Fish & Chips? Yes')
        ->and($query['www.pinterest.com']['description'])->toBe('Fish & Chips? Yes')
        ->and($query['wa.me']['text'])->toStartWith('Fish & Chips? Yes ');

    // The ampersand is percent-encoded in the href itself.
    expect($html)->toContain('text=Fish%20%26%20Chips%3F%20Yes');
});

it('shares the clean canonical, never the request query string', function () {
    // Url::normalize() rebuilds a canonical as scheme + host + path and drops
    // the query, so tracking params on the request cannot leak into a share.
    shareableProduct('clean-tea', ['name' => ['en' => 'Clean Tea', 'bn' => '']]);

    $html = $this->get('/products/clean-tea?utm_source=newsletter&utm_medium=email')->assertOk()->getContent();

    $query = shareQueryMap($html);

    expect($query['www.facebook.com']['u'])->toBe(url('/products/clean-tea'))
        ->and($query['www.facebook.com'])->not->toHaveKey('utm_source')
        ->and($html)->toContain('<meta property="og:url" content="'.url('/products/clean-tea').'">');
});

it('shares the og title the page advertises, not the raw product name', function () {
    shareableProduct(
        'og-tea',
        ['name' => ['en' => 'Raw Product Name', 'bn' => '']],
        ['og_title' => ['en' => 'Hand-picked Assam, loose leaf', 'bn' => '']],
    );

    $html = $this->get('/products/og-tea')->assertOk()->getContent();

    $query = shareQueryMap($html);

    // Facebook takes no text at all — it scrapes og: — so the title only
    // appears in the networks that accept one. Either way it has to be the
    // card's title, or the share and the preview disagree.
    expect($query['twitter.com']['text'])->toBe('Hand-picked Assam, loose leaf')
        ->and($query['t.me']['text'])->toBe('Hand-picked Assam, loose leaf')
        ->and($query['www.pinterest.com']['description'])->toBe('Hand-picked Assam, loose leaf')
        ->and($html)->toContain('<meta property="og:title" content="Hand-picked Assam, loose leaf">');
});

it('folds the url into a single text blob for whatsapp', function () {
    shareableProduct('wa-tea', ['name' => ['en' => 'WhatsApp Tea', 'bn' => '']]);

    $html = $this->get('/products/wa-tea')->assertOk()->getContent();

    $query = shareQueryMap($html);

    // wa.me has no url parameter at all — text and url travel as one string, so
    // asserting on a separate url key would pass while WhatsApp got nothing.
    expect($query['wa.me'])->toHaveKey('text')
        ->and($query['wa.me'])->not->toHaveKey('url')
        ->and($query['wa.me']['text'])->toContain(url('/products/wa-tea'));
});

it('passes the og image to pinterest only when there is one', function () {
    shareableProduct(
        'pinned-tea',
        ['name' => ['en' => 'Pinned Tea', 'bn' => '']],
        ['og_image' => 'https://cdn.example.com/tea.jpg'],
    );

    $withImage = $this->get('/products/pinned-tea')->assertOk()->getContent();

    expect(shareQueryMap($withImage)['www.pinterest.com']['media'])->toBe('https://cdn.example.com/tea.jpg');

    // A product with no image anywhere gets no media key rather than an empty
    // one, because Pinterest renders a dead button for a blank media param.
    shareableProduct('unpinned-tea', ['name' => ['en' => 'Unpinned Tea', 'bn' => '']]);

    $withoutImage = $this->get('/products/unpinned-tea')->assertOk()->getContent();

    expect(shareQueryMap($withoutImage)['www.pinterest.com'])->not->toHaveKey('media')
        // Facebook needs no image either: the page's own og:image is scraped.
        ->and($withoutImage)->not->toContain('media=');
});

it('opens a popup only for the networks that render a share sheet on their own domain', function () {
    shareableProduct('popup-tea', ['name' => ['en' => 'Popup Tea', 'bn' => '']]);

    $html = $this->get('/products/popup-tea')->assertOk()->getContent();

    // Matched per host rather than by counting the attribute: the inline script
    // below the row also names it, as the delegated click selector.
    preg_match_all('/href="([^"]*)"\s*data-share-popup/', $html, $matches);

    $popups = array_map(
        fn (string $href) => (string) parse_url(html_entity_decode($href, ENT_QUOTES), PHP_URL_HOST),
        $matches[1]
    );

    // WhatsApp, Telegram and mailto hand off to an app or the mail client;
    // a popup window in front of that is worse than letting the page navigate.
    expect($popups)->toEqualCanonicalizing([
        'www.facebook.com',
        'www.linkedin.com',
        'www.pinterest.com',
        'twitter.com',
    ])
        // target="_blank" rides the same flag, so none of the hand-off links
        // leave a blank tab behind.
        ->and($html)->toContain('href="https://wa.me/?')
        ->and(preg_match('/href="https:\/\/wa\.me\/[^"]*"\s*target/', $html))->toBe(0)
        ->and(preg_match('/href="mailto:[^"]*"\s*target/', $html))->toBe(0);
});

it('carries the url on the copy-link button', function () {
    shareableProduct('copy-tea', ['name' => ['en' => 'Copy Tea', 'bn' => '']]);

    $html = $this->get('/products/copy-tea')->assertOk()->getContent();

    expect($html)->toContain('data-share-copy="'.url('/products/copy-tea').'"')
        // The clipboard API is undefined over plain http, which is still how a
        // staging site gets reached, so the execCommand path has to be there.
        ->and($html)->toContain('execCommand')
        ->and($html)->toContain('window.isSecureContext');
});

it('leaves the share row off other themes\' product pages', function () {
    Setting::set('site_theme', 'portfolio');

    shareableProduct('off-tea', ['name' => ['en' => 'Off Tea', 'bn' => '']]);

    $this->get('/products/off-tea')
        ->assertNotFound()
        ->assertDontSee('facebook.com/sharer/sharer.php');
});
