<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;

use function Pest\Laravel\get;

beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Blue Tea', 'bn' => ''], 'sort_order' => 0]);

    pairPageFor($product, 'product', 'blue-tea', User::factory()->create()->id);
});

it('renders the regular product page for a plain visit', function () {
    get('/products/blue-tea')
        ->assertOk()
        ->assertSee('Blue Tea')
        ->assertDontSee('id="order"', false);
});

it('renders the campaign landing page when the visit carries a fbclid', function () {
    get('/products/blue-tea?fbclid=egfad')
        ->assertOk()
        ->assertSee('Blue Tea')
        ->assertSee('id="order"', false);
});
