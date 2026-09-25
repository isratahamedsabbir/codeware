<?php

use App\Models\Advertisement;
use App\Models\Feature;
use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

/**
 * A published product whose paired page gives it a URL-able slug and which
 * carries both description and specifications so the Description/Specifications
 * tabs (and the ad slot beside them) actually render.
 */
function adProduct(string $slug): Product
{
    $product = Product::factory()->published()->create([
        'description' => ['en' => '<p>Good stuff</p>', 'bn' => ''],
        'specifications' => ['en' => '<ul><li>Spec A</li></ul>', 'bn' => ''],
    ]);

    pairPageFor($product, 'product', $slug, User::factory()->create()->id);

    return $product;
}

it('shows an active advertisement beside the product details section', function () {
    $ad = Advertisement::factory()->create([
        'name' => 'Flash Banner',
        'image' => '/storage/media/ad.png',
        'url' => 'https://example.com/landing',
    ]);
    adProduct('banner-product');

    get('/products/banner-product')
        ->assertOk()
        ->assertSee('Sponsored')
        ->assertSee('Flash Banner')
        ->assertSee('/storage/media/ad.png')
        ->assertSee('/ad/'.$ad->code)
        ->assertSee('example.com');
});

it('hides the advertisement when the advertisements feature is off', function () {
    Feature::create(['key' => 'advertisements', 'label' => 'Advertisements', 'is_enabled' => false]);
    Advertisement::factory()->create(['name' => 'Hidden Banner']);
    adProduct('banner-product');

    get('/products/banner-product')
        ->assertOk()
        ->assertDontSee('Sponsored')
        ->assertDontSee('Hidden Banner');
});

it('does not show expired or scheduled advertisements', function () {
    Advertisement::factory()->expired()->create(['name' => 'Expired Banner']);
    Advertisement::factory()->scheduled()->create(['name' => 'Scheduled Banner']);
    adProduct('banner-product');

    get('/products/banner-product')
        ->assertOk()
        ->assertDontSee('Sponsored')
        ->assertDontSee('Expired Banner')
        ->assertDontSee('Scheduled Banner');
});

it('counts the click and redirects to the destination URL', function () {
    $ad = Advertisement::factory()->create(['url' => 'https://example.com/win']);

    get('/ad/'.$ad->code)
        ->assertRedirect('https://example.com/win');

    expect($ad->refresh()->clicks)->toBe(1);
});

it('falls back to the homepage when an advertisement has no destination URL', function () {
    $ad = Advertisement::factory()->create(['url' => null]);

    get('/ad/'.$ad->code)->assertRedirect(url('/'));

    expect($ad->refresh()->clicks)->toBe(1);
});

it('returns 404 for an unknown or out-of-window advertisement code', function () {
    $expired = Advertisement::factory()->expired()->create();

    get('/ad/'.$expired->code)->assertNotFound();
    get('/ad/AD-UNKNOWN_CODE')->assertNotFound();
});
