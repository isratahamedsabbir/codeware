<?php

use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVendor;
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
    expect($product->product_type)->toBe('physical');
});

it('admin can create a digital product', function () {
    Sanctum::actingAs($this->admin);

    $response = $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'E-book', 'bn' => ''],
        'product_type' => 'digital',
    ])->assertCreated();

    expect(Product::findOrFail($response->json('data.id'))->product_type)->toBe('digital');
});

it('rejects an invalid product_type', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'Bad Type Product', 'bn' => ''],
        'product_type' => 'subscription',
    ])->assertStatus(422)->assertJsonValidationErrors('product_type');
});

it('admin can create a product with a brand assigned', function () {
    Sanctum::actingAs($this->admin);
    $brand = ProductBrand::factory()->create();

    $response = $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'Branded Product', 'bn' => ''],
        'brand_id' => $brand->id,
        'status' => 'inactive',
    ])->assertCreated();

    $product = Product::findOrFail($response->json('data.id'));
    expect($product->brand_id)->toBe($brand->id);
    expect($product->brand->name)->toBe($brand->name);
});

it('rejects a product with a non-existent brand_id', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'Bad Brand Product', 'bn' => ''],
        'brand_id' => 999999,
    ])->assertStatus(422)->assertJsonValidationErrors('brand_id');
});

it('admin can create a product with a vendor and sku', function () {
    Sanctum::actingAs($this->admin);
    $vendor = ProductVendor::factory()->create();

    $response = $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'Vendored Product', 'bn' => ''],
        'vendor_id' => $vendor->id,
        'sku' => 'ABC-123',
        'status' => 'inactive',
    ])->assertCreated();

    $product = Product::findOrFail($response->json('data.id'));
    expect($product->vendor_id)->toBe($vendor->id);
    expect($product->vendor->name)->toBe($vendor->name);
    expect($product->sku)->toBe('ABC-123');
});

it('rejects a duplicate sku', function () {
    Sanctum::actingAs($this->admin);
    Product::factory()->create(['sku' => 'DUP-1']);

    $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'Duplicate SKU Product', 'bn' => ''],
        'sku' => 'DUP-1',
    ])->assertStatus(422)->assertJsonValidationErrors('sku');
});

it('admin can update a product\'s vendor and sku', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create();
    $vendor = ProductVendor::factory()->create();

    $this->putJson("/api/v1/admin/products/{$product->id}", [
        'vendor_id' => $vendor->id,
        'sku' => 'XYZ-789',
    ])->assertOk();

    $product->refresh();
    expect($product->vendor_id)->toBe($vendor->id);
    expect($product->sku)->toBe('XYZ-789');
});

it('allows keeping a product\'s own sku unchanged when updating', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create(['sku' => 'KEEP-1']);

    $this->putJson("/api/v1/admin/products/{$product->id}", [
        'sku' => 'KEEP-1',
        'status' => 'active',
    ])->assertOk();

    expect($product->fresh()->sku)->toBe('KEEP-1');
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

it('admin can update a product\'s brand, and clear it back to null', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create();
    $brand = ProductBrand::factory()->create();

    $this->putJson("/api/v1/admin/products/{$product->id}", ['brand_id' => $brand->id])
        ->assertOk();
    expect(Product::find($product->id)->brand_id)->toBe($brand->id);

    $this->putJson("/api/v1/admin/products/{$product->id}", ['brand_id' => null])
        ->assertOk();
    expect(Product::find($product->id)->brand_id)->toBeNull();
});

it('admin can update a product\'s type', function () {
    Sanctum::actingAs($this->admin);
    $product = Product::factory()->create(['product_type' => 'physical']);

    $this->putJson("/api/v1/admin/products/{$product->id}", ['product_type' => 'digital'])
        ->assertOk();

    expect(Product::find($product->id)->product_type)->toBe('digital');
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
