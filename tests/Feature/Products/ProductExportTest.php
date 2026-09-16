<?php

use App\Models\Feature;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports products as a downloadable csv', function () {
    Product::factory()->published()->create(['name' => ['en' => 'Widget', 'bn' => 'উইজেট']]);

    $response = $this->get(route('admin.products.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Name (EN)')
        ->and($csv)->toContain('Widget');
});

it('only includes products matching the search filter', function () {
    Product::factory()->published()->create(['name' => ['en' => 'Blue Widget', 'bn' => '']]);
    Product::factory()->published()->create(['name' => ['en' => 'Red Gadget', 'bn' => '']]);

    $csv = $this->get(route('admin.products.export', ['search' => 'Widget']))->streamedContent();

    expect($csv)->toContain('Blue Widget')
        ->and($csv)->not->toContain('Red Gadget');
});

it('only includes products matching the status filter', function () {
    Product::factory()->published()->create(['name' => ['en' => 'Active Product', 'bn' => '']]);
    Product::factory()->draft()->create(['name' => ['en' => 'Draft Product', 'bn' => '']]);

    $csv = $this->get(route('admin.products.export', ['status' => 'active']))->streamedContent();

    expect($csv)->toContain('Active Product')
        ->and($csv)->not->toContain('Draft Product');
});

it('includes the category name for products that have one', function () {
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Electronics', 'bn' => '']]);
    $product = Product::factory()->published()->create(['name' => ['en' => 'Laptop', 'bn' => '']]);
    $product->categories()->attach($category);

    $csv = $this->get(route('admin.products.export'))->streamedContent();

    expect($csv)->toContain('Electronics');
});

it('exports only the requested product ids, ignoring search and status', function () {
    $included = Product::factory()->published()->create(['name' => ['en' => 'Selected Widget', 'bn' => '']]);
    $excluded = Product::factory()->published()->create(['name' => ['en' => 'Other Widget', 'bn' => '']]);

    $csv = $this->get(route('admin.products.export', ['ids' => [$included->id], 'search' => 'nonexistent']))->streamedContent();

    expect($csv)->toContain('Selected Widget')
        ->not->toContain('Other Widget');
});

it('is blocked when the products feature is disabled', function () {
    Feature::create(['key' => 'products', 'label' => 'Products', 'is_enabled' => false]);

    $this->get(route('admin.products.export'))->assertNotFound();
});
