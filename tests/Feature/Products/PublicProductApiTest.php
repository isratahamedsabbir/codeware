<?php

use App\Models\Product;
use App\Models\ProductCategory;

it('returns product categories ordered by sort_order', function () {
    ProductCategory::factory()->create(['name' => ['en' => 'Seeds', 'bn' => ''], 'sort_order' => 2]);
    ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => ''], 'sort_order' => 1]);

    $response = $this->getJson('/api/v1/product-categories');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'fertilizers');
});

it('product categories response includes expected fields', function () {
    ProductCategory::factory()->create();

    $this->getJson('/api/v1/product-categories')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'description', 'icon', 'sort_order']]]);
});

it('returns only active products on public listing', function () {
    Product::factory()->published()->create(['name' => ['en' => 'Visible', 'bn' => '']]);
    Product::factory()->draft()->create(['name' => ['en' => 'Hidden', 'bn' => '']]);

    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Visible');
});

it('filters products by category slug', function () {
    $cat = ProductCategory::factory()->create(['slug' => 'fertilizers']);
    Product::factory()->published()->create(['product_category_id' => $cat->id]);
    Product::factory()->published()->create();

    $this->getJson('/api/v1/products?category=fertilizers')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters products by search term', function () {
    Product::factory()->published()->create(['name' => ['en' => 'Super Fertilizer X', 'bn' => '']]);
    Product::factory()->published()->create(['name' => ['en' => 'Basic Seed Pack', 'bn' => '']]);

    $this->getJson('/api/v1/products?search=Fertilizer')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Super Fertilizer X');
});

it('filters products by featured flag', function () {
    Product::factory()->published()->featured()->create();
    Product::factory()->published()->create();

    $this->getJson('/api/v1/products?featured=1')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns paginated products with meta', function () {
    Product::factory()->count(5)->published()->create();

    $this->getJson('/api/v1/products?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.per_page', 2);
});

it('returns full product detail by slug with gallery and related', function () {
    $cat = ProductCategory::factory()->create();
    $product = Product::factory()->published()->create([
        'product_category_id' => $cat->id,
        'name' => ['en' => 'Detail Product', 'bn' => ''],
    ]);
    Product::factory()->published()->create(['product_category_id' => $cat->id]);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $product->slug)
        ->assertJsonStructure(['data' => [
            'id', 'slug', 'name', 'description',
            'featured_image', 'gallery', 'related_products', 'category',
        ]])
        ->assertJsonCount(1, 'data.related_products');
});

it('related_products excludes current product', function () {
    $cat = ProductCategory::factory()->create();
    $product = Product::factory()->published()->create(['product_category_id' => $cat->id]);

    $response = $this->getJson("/api/v1/products/{$product->slug}");

    $ids = collect($response->json('data.related_products'))->pluck('id');
    expect($ids)->not->toContain($product->id);
});

it('returns 404 for inactive product slug on public endpoint', function () {
    $product = Product::factory()->draft()->create();

    $this->getJson("/api/v1/products/{$product->slug}")->assertNotFound();
});

it('returns product name for bn locale', function () {
    Product::factory()->published()->create(['name' => ['en' => 'English Name', 'bn' => 'বাংলা নাম']]);

    $this->getJson('/api/v1/products?locale=bn')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'বাংলা নাম');
});

it('public product detail includes puck_data', function () {
    $puckData = ['root' => ['props' => []], 'content' => [['type' => 'ProductHero', 'props' => []]]];
    $product  = Product::factory()->published()->create(['puck_data' => $puckData]);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.puck_data', $puckData);
});

it('public product detail includes faq for locale', function () {
    $faq     = [['question' => ['en' => 'What is this?', 'bn' => 'এটি কি?'], 'answer' => ['en' => 'A product.', 'bn' => 'একটি পণ্য।']]];
    $product = Product::factory()->published()->create(['faq' => $faq]);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.faq.0.question', 'What is this?')
        ->assertJsonPath('data.faq.0.answer', 'A product.');
});

it('public product detail returns faq in bn locale', function () {
    $faq     = [['question' => ['en' => 'What is this?', 'bn' => 'এটি কি?'], 'answer' => ['en' => 'A product.', 'bn' => 'একটি পণ্য।']]];
    $product = Product::factory()->published()->create(['faq' => $faq]);

    $this->getJson("/api/v1/products/{$product->slug}?locale=bn")
        ->assertOk()
        ->assertJsonPath('data.faq.0.question', 'এটি কি?')
        ->assertJsonPath('data.faq.0.answer', 'একটি পণ্য।');
});

it('public product listing does not expose puck_data', function () {
    Product::factory()->published()->create(['puck_data' => ['root' => ['props' => []], 'content' => []]]);

    $response = $this->getJson('/api/v1/products')->assertOk();

    expect($response->json('data.0'))->not->toHaveKey('puck_data');
});
