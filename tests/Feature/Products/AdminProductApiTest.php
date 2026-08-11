<?php

use App\Models\MediaLibrary;
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
        'name'       => ['en' => 'New Category', 'bn' => 'নতুন বিভাগ'],
        'icon'       => 'leaf',
        'sort_order' => 1,
    ])->assertCreated()->assertJsonPath('data.slug', 'new-category');

    expect(ProductCategory::where('slug', 'new-category')->exists())->toBeTrue();
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

    $this->postJson('/api/v1/admin/products', [
        'name'                => ['en' => 'Test Product', 'bn' => ''],
        'product_category_id' => $cat->id,
        'status'              => 'inactive',
    ])->assertCreated()->assertJsonPath('data.slug', 'test-product');

    expect(Product::where('slug', 'test-product')->exists())->toBeTrue();
});

it('admin can create a product with gallery sync', function () {
    Sanctum::actingAs($this->admin);
    $media1 = MediaLibrary::factory()->create();
    $media2 = MediaLibrary::factory()->create();

    $response = $this->postJson('/api/v1/admin/products', [
        'name'      => ['en' => 'Gallery Product', 'bn' => ''],
        'status'    => 'inactive',
        'media_ids' => [$media1->id, $media2->id],
    ]);

    $response->assertCreated();
    $product = Product::where('slug', 'gallery-product')->firstOrFail();
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

it('admin can save puck_data when updating a product', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create();

    $puckData = ['root' => ['props' => []], 'content' => [['type' => 'ProductHero', 'props' => []]]];

    $this->putJson("/api/v1/admin/products/{$product->id}", ['puck_data' => $puckData])
        ->assertOk();

    expect(Product::find($product->id)->puck_data)->toBe($puckData);
});

it('admin can save faq when updating a product', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create();

    $faq = [
        ['question' => ['en' => 'What is this?', 'bn' => 'এটি কি?'], 'answer' => ['en' => 'A product.', 'bn' => 'একটি পণ্য।']],
    ];

    $this->putJson("/api/v1/admin/products/{$product->id}", ['faq' => $faq])
        ->assertOk();

    $saved = Product::find($product->id)->faq;
    expect($saved[0]['question']['en'])->toBe('What is this?');
    expect($saved[0]['question']['bn'])->toBe('এটি কি?');
    expect($saved[0]['answer']['en'])->toBe('A product.');
    expect($saved[0]['answer']['bn'])->toBe('একটি পণ্য।');
});

it('admin can create a product with puck_data and faq', function () {
    Sanctum::actingAs($this->admin);

    $puckData = ['root' => ['props' => []], 'content' => []];
    $faq      = [['question' => ['en' => 'Q?', 'bn' => ''], 'answer' => ['en' => 'A.', 'bn' => '']]];

    $this->postJson('/api/v1/admin/products', [
        'name'      => ['en' => 'Puck Product', 'bn' => ''],
        'status'    => 'inactive',
        'puck_data' => $puckData,
        'faq'       => $faq,
    ])->assertCreated();

    $product = Product::where('slug', 'puck-product')->firstOrFail();
    expect($product->puck_data)->toBe($puckData);
    expect($product->faq[0]['question']['en'])->toBe('Q?');
    expect($product->faq[0]['answer']['en'])->toBe('A.');
});
