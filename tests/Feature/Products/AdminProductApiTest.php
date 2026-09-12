<?php

use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

// Auth guards
it('rejects unauthenticated requests to admin product-categories api', function () {
    $this->getJson('/api/v1/admin/product-categories')->assertUnauthorized();
});

it('rejects non-admin users from admin product api', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
    $this->getJson('/api/v1/admin/products')->assertForbidden();
});

// Product Categories CRUD
it('admin can list product categories', function () {
    Sanctum::actingAs($this->admin);
    ProductCategory::factory()->count(3)->create();

    $this->getJson('/api/v1/admin/product-categories')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('admin can create a product category', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/product-categories', [
        'name' => ['en' => 'New Category', 'bn' => 'নতুন বিভাগ'],
        'icon' => 'leaf',
        'sort_order' => 1,
    ])->assertCreated()->assertJsonPath('data.slug', 'new_category');

    expect(Page::where(['type' => 'product_category', 'slug' => 'new_category'])->exists())->toBeTrue();
});

it('admin can update a product category', function () {
    Sanctum::actingAs($this->admin);
    $cat = ProductCategory::factory()->create();

    $this->putJson("/api/v1/admin/product-categories/{$cat->id}", [
        'name' => ['en' => 'Updated Name', 'bn' => ''],
    ])->assertOk();

    expect(ProductCategory::find($cat->id)->getTranslation('name', 'en'))->toBe('Updated Name');
});

it('admin can delete a product category', function () {
    Sanctum::actingAs($this->admin);
    $cat = ProductCategory::factory()->create();

    $this->deleteJson("/api/v1/admin/product-categories/{$cat->id}")->assertNoContent();

    expect(ProductCategory::find($cat->id))->toBeNull();
});

// Products CRUD
it('admin can list all products including inactive', function () {
    Sanctum::actingAs($this->admin);
    Product::factory()->published()->create();
    Product::factory()->draft()->create();

    $this->getJson('/api/v1/admin/products')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

it('admin can create a product', function () {
    Sanctum::actingAs($this->admin);
    $cat = ProductCategory::factory()->create();

    $response = $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'Test Product', 'bn' => ''],
        'category_ids' => [$cat->id],
        'status' => 'inactive',
    ])->assertCreated()->assertJsonPath('data.slug', 'test_product');

    expect(Page::where(['type' => 'product', 'slug' => 'test_product'])->exists())->toBeTrue();

    $product = Product::findOrFail($response->json('data.id'));
    expect($product->categories->pluck('id')->all())->toBe([$cat->id]);
});

it('admin can create a product with gallery sync', function () {
    Sanctum::actingAs($this->admin);
    $media1 = MediaLibrary::factory()->create();
    $media2 = MediaLibrary::factory()->create();

    $response = $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'Gallery Product', 'bn' => ''],
        'status' => 'inactive',
        'media_ids' => [$media1->id, $media2->id],
    ]);

    $response->assertCreated();
    $page = Page::where(['type' => 'product', 'slug' => 'gallery_product'])->firstOrFail();
    $product = Product::findOrFail($page->product_id);
    expect($product->gallery()->count())->toBe(2);
    expect($product->gallery()->wherePivot('sort_order', 0)->first()->id)->toBe($media1->id);
});

it('admin can update a product', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create();

    $this->putJson("/api/v1/admin/products/{$product->id}", [
        'status' => 'active',
    ])->assertOk();

    expect(Product::find($product->id)->status)->toBe('active');
});

it('admin can soft-delete a product', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create();

    $this->deleteJson("/api/v1/admin/products/{$product->id}")->assertNoContent();

    expect(Product::find($product->id))->toBeNull();
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
});

it('create product fails validation without required name', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/products', ['status' => 'inactive'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name.en']);
});

it('admin can save puck_data when updating a product, onto the paired page', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create();

    $puckData = ['root' => ['props' => []], 'content' => [['type' => 'ProductHero', 'props' => []]]];

    $this->putJson("/api/v1/admin/products/{$product->id}", ['puck_data' => $puckData])
        ->assertOk();

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->sole();
    expect($page->puck_data)->toBe($puckData);
});

it('admin can save faq when updating a product', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create();

    $faq = [
        ['question' => 'What is this?', 'answer' => 'A product.', 'is_active' => false],
    ];

    $this->putJson("/api/v1/admin/products/{$product->id}", ['faq' => $faq])
        ->assertOk();

    $saved = Product::find($product->id)->faqs->first();
    expect($saved->question)->toBe('What is this?');
    expect($saved->answer)->toBe('A product.');
    expect($saved->is_active)->toBeFalse();
});

it('admin can create a product with puck_data and faq, puck_data landing on the paired page', function () {
    Sanctum::actingAs($this->admin);

    $puckData = ['root' => ['props' => []], 'content' => []];
    $faq = [['question' => 'Q?', 'answer' => 'A.']];

    $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'Puck Product', 'bn' => ''],
        'status' => 'inactive',
        'puck_data' => $puckData,
        'faq' => $faq,
    ])->assertCreated();

    $page = Page::where(['type' => 'product', 'slug' => 'puck_product'])->sole();
    $product = Product::findOrFail($page->product_id);
    $savedFaq = $product->faqs->first();
    expect($page->puck_data)->toBe($puckData);
    expect($savedFaq->question)->toBe('Q?');
    expect($savedFaq->answer)->toBe('A.');
});
