<?php

use App\Models\CmsSection;
use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('returns product categories ordered by sort_order', function () {
    $seeds = ProductCategory::factory()->create(['name' => ['en' => 'Seeds', 'bn' => ''], 'sort_order' => 2]);
    pairPageFor($seeds, 'product_category', 'seeds', $this->admin->id);
    $fertilizers = ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => ''], 'sort_order' => 1]);
    pairPageFor($fertilizers, 'product_category', 'fertilizers', $this->admin->id);

    $response = $this->getJson('/api/v1/product-categories');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'fertilizers');
});

it('product categories response includes expected fields', function () {
    ProductCategory::factory()->create();

    $this->getJson('/api/v1/product-categories')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'icon', 'sort_order', 'page']]]);
});

it('product categories listing includes puck_data nested under page', function () {
    $puckData = ['root' => ['props' => []], 'content' => [['type' => 'CategoryHero']]];
    $category = ProductCategory::factory()->create();
    Page::create([
        'type' => 'product_category', 'category_id' => $category->id, 'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'Title'], 'slug' => 'category-hero', 'status' => 'active',
        'puck_data' => $puckData,
    ]);

    $this->getJson('/api/v1/product-categories')
        ->assertOk()
        ->assertJsonPath('data.0.page.puck_data', $puckData);
});

it('returns a single product category by slug with page data', function () {
    $puckData = ['root' => ['props' => []], 'content' => [['type' => 'CategoryHero']]];
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    $page = pairPageFor($category, 'product_category', 'fertilizers', $this->admin->id);
    $page->update(['puck_data' => $puckData, 'seo_title' => 'Fertilizers SEO Title']);

    $this->getJson('/api/v1/product-categories/fertilizers')
        ->assertOk()
        ->assertJsonPath('data.slug', 'fertilizers')
        ->assertJsonPath('data.name', 'Fertilizers')
        ->assertJsonPath('data.page.puck_data', $puckData)
        ->assertJsonPath('data.page.meta_data.seo_title', 'Fertilizers SEO Title')
        ->assertJsonStructure(['data' => ['id', 'name', 'slug', 'icon', 'sort_order', 'page' => [
            'meta_data' => ['seo_title', 'seo_description', 'og_title', 'og_description', 'og_image', 'twitter_title', 'twitter_description', 'twitter_image', 'no_index', 'no_follow'],
            'puck_data',
            'constant',
        ]]]);
});

it('includes the paired page\'s own constant map inside page.constant', function () {
    $category = ProductCategory::factory()->create();
    $page = pairPageFor($category, 'product_category', 'constant-category', $this->admin->id);
    $page->update(['constant' => [['key' => 'badge', 'value' => 'New']]]);

    $this->getJson('/api/v1/product-categories/constant-category')
        ->assertOk()
        ->assertJsonPath('data.page.constant.badge', 'New');
});

it('includes the page\'s cms sections on a single product category, but not on the listing', function () {
    $category = ProductCategory::factory()->create();
    $page = pairPageFor($category, 'product_category', 'cms-category', $this->admin->id);
    CmsSection::factory()->create([
        'page_id' => $page->id, 'name' => 'hero', 'status' => 'active',
        'cards' => [['image' => '/hero.jpg', 'title' => 'Hero', 'description' => 'Section']],
        'constant' => [['key' => 'note', 'value' => 'Hello']],
    ]);

    $this->getJson('/api/v1/product-categories/cms-category')
        ->assertOk()
        ->assertJsonCount(1, 'data.cms')
        ->assertJsonPath('data.cms.0.name', 'hero')
        ->assertJsonPath('data.cms.0.cards.0.title', 'Hero')
        ->assertJsonPath('data.cms.0.constant.note', 'Hello');

    $this->getJson('/api/v1/product-categories')
        ->assertOk()
        ->assertJsonMissingPath('data.0.cms');
});

it('returns 404 for an unknown product category slug', function () {
    $this->getJson('/api/v1/product-categories/does-not-exist')->assertNotFound();
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
    $cat = ProductCategory::factory()->create();
    pairPageFor($cat, 'product_category', 'fertilizers', $this->admin->id);
    $product = Product::factory()->published()->create();
    $product->categories()->attach($cat);
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
        'name' => ['en' => 'Detail Product', 'bn' => ''],
    ]);
    $product->categories()->attach($cat);
    pairPageFor($product, 'product', 'detail-product', $this->admin->id);
    $sibling = Product::factory()->published()->create();
    $sibling->categories()->attach($cat);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $product->slug)
        ->assertJsonStructure(['data' => [
            'id', 'slug', 'name', 'description',
            'featured_image', 'gallery', 'related_products', 'categories',
        ]])
        ->assertJsonCount(1, 'data.related_products');
});

it('related_products excludes current product', function () {
    $cat = ProductCategory::factory()->create();
    $product = Product::factory()->published()->create();
    $product->categories()->attach($cat);
    pairPageFor($product, 'product', 'related-excludes-self', $this->admin->id);

    $response = $this->getJson("/api/v1/products/{$product->slug}");

    $ids = collect($response->json('data.related_products'))->pluck('id');
    expect($ids)->not->toContain($product->id);
});

it('includes the paired page\'s constant map and cms sections on a single product', function () {
    $product = Product::factory()->published()->create();
    $page = pairPageFor($product, 'product', 'cms-product', $this->admin->id);
    $page->update(['constant' => [['key' => 'warranty', 'value' => '1 year']]]);
    CmsSection::factory()->create([
        'page_id' => $page->id, 'name' => 'specs', 'status' => 'active',
        'constant' => [['key' => 'weight', 'value' => '2kg']],
    ]);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.page.constant.warranty', '1 year')
        ->assertJsonPath('data.cms.0.name', 'specs')
        ->assertJsonPath('data.cms.0.constant.weight', '2kg');

    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonMissingPath('data.0.cms');
});

it('returns 404 for inactive product slug on public endpoint', function () {
    $product = Product::factory()->draft()->create();
    pairPageFor($product, 'product', 'inactive-product', $this->admin->id);

    $this->getJson("/api/v1/products/{$product->slug}")->assertNotFound();
});

it('returns product name for bn locale', function () {
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    Product::factory()->published()->create(['name' => ['en' => 'English Name', 'bn' => 'বাংলা নাম']]);

    $this->getJson('/api/v1/products?locale=bn')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'বাংলা নাম');
});

it('public product detail includes puck_data from the paired page', function () {
    $puckData = ['root' => ['props' => []], 'content' => [['type' => 'ProductHero', 'props' => []]]];
    $product = Product::factory()->published()->create();
    pairPageFor($product, 'product', 'puck-detail-product', $this->admin->id);
    $product->page->update(['puck_data' => $puckData]);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.page.puck_data', $puckData);
});

it('public product detail includes faq for locale', function () {
    $faq = [['question' => ['en' => 'What is this?', 'bn' => 'এটি কি?'], 'answer' => ['en' => 'A product.', 'bn' => 'একটি পণ্য।']]];
    $product = Product::factory()->published()->create(['faq' => $faq]);
    pairPageFor($product, 'product', 'faq-product', $this->admin->id);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.faq.0.question', 'What is this?')
        ->assertJsonPath('data.faq.0.answer', 'A product.');
});

it('public product detail returns faq in bn locale', function () {
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    $faq = [['question' => ['en' => 'What is this?', 'bn' => 'এটি কি?'], 'answer' => ['en' => 'A product.', 'bn' => 'একটি পণ্য।']]];
    $product = Product::factory()->published()->create(['faq' => $faq]);
    pairPageFor($product, 'product', 'faq-product-bn', $this->admin->id);

    $this->getJson("/api/v1/products/{$product->slug}?locale=bn")
        ->assertOk()
        ->assertJsonPath('data.faq.0.question', 'এটি কি?')
        ->assertJsonPath('data.faq.0.answer', 'একটি পণ্য।');
});

it('public product listing includes puck_data nested under page, never at the top level', function () {
    $puckData = ['root' => ['props' => []], 'content' => []];
    $product = Product::factory()->published()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'Title'], 'slug' => 'listing-puck-product', 'status' => 'active',
        'puck_data' => $puckData,
    ]);

    $response = $this->getJson('/api/v1/products')->assertOk();

    expect($response->json('data.0'))->not->toHaveKey('puck_data')
        ->and($response->json('data.0.page.puck_data'))->toBe($puckData);
});
