<?php

use App\Livewire\Frontend\HeaderSearch;
use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

function headerSearchProduct(string $slug, array $attributes = []): Product
{
    $product = Product::factory()->published()->create($attributes + ['sort_order' => 0, 'quantity' => 10]);

    pairPageFor($product, 'product', $slug, User::factory()->create()->id);

    return $product;
}

it('suggests active products as you type and lists them with prices', function () {
    headerSearchProduct('red-shoe', ['name' => ['en' => 'Red Running Shoe', 'bn' => ''], 'price' => 1200]);

    Livewire::test(HeaderSearch::class)
        ->set('query', 'Red')
        ->assertSee('Red Running Shoe')
        ->assertSee('৳ 1,200');
});

it('matches the bangla name too and links to the product page', function () {
    $product = headerSearchProduct('lal-juto', ['name' => ['en' => 'Lal Juto', 'bn' => 'লাল জুতো']]);

    Livewire::test(HeaderSearch::class)
        ->set('query', 'জুতো')
        ->assertSee('Lal Juto')
        ->assertSeeHtml(route('products.show', $product->slug));
});

it('suggests nothing for an empty query but matches a single character', function () {
    headerSearchProduct('shirt', ['name' => ['en' => 'Cotton Shirt', 'bn' => '']]);

    Livewire::test(HeaderSearch::class)
        ->set('query', '')
        ->assertSet('suggestions', []);

    Livewire::test(HeaderSearch::class)
        ->set('query', 'c')
        ->assertSee('Cotton Shirt');
});

it('matches product names case-insensitively', function () {
    headerSearchProduct('hoodie', ['name' => ['en' => 'Hoodie', 'bn' => '']]);

    Livewire::test(HeaderSearch::class)
        ->set('query', 'hoodie')
        ->assertSee('Hoodie');
});

it('never suggests inactive products', function () {
    Product::factory()->create(['name' => ['en' => 'Hidden Widget', 'bn' => ''], 'status' => 'inactive', 'sort_order' => 0]);

    Livewire::test(HeaderSearch::class)
        ->set('query', 'Hidden')
        ->assertSet('suggestions', []);
});

it('renders the desktop search form on the storefront', function () {
    headerSearchProduct('shop-item', ['name' => ['en' => 'Shop Item', 'bn' => '']]);

    // The header search is part of the ecommerce theme's shop chrome, and the
    // shop only exists on a theme that ships it (see Themes::view()).
    Setting::set('site_theme', 'ecommerce');

    get('/shop')
        ->assertOk()
        ->assertSee('Search products');
});
