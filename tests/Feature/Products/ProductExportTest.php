<?php

use App\Models\Feature;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVendor;
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

it('exports the product fields the importer expects back', function () {
    $category = ProductCategory::factory()->create(['name' => ['en' => 'Electronics', 'bn' => '']]);
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme', 'bn' => '']]);
    $vendor = ProductVendor::factory()->create(['name' => 'Acme Supplies']);
    $product = Product::factory()->published()->create([
        'name' => ['en' => 'Laptop', 'bn' => 'ল্যাপটপ'],
        'sku' => 'LAP-1',
        'quantity' => 7,
        'price' => 1200.50,
        'discount_price' => 999.99,
        'product_type' => 'digital',
        'charge_shipping' => false,
        'is_upcoming' => true,
        'is_featured' => true,
        'excerpt' => ['en' => 'Short summary', 'bn' => 'সংক্ষিপ্ত সারাংশ'],
        'description' => ['en' => 'Full description', 'bn' => 'বিস্তারিত বর্ণনা'],
    ]);
    $product->categories()->attach($category);
    $product->update(['brand_id' => $brand->id, 'vendor_id' => $vendor->id]);

    $csv = $this->get(route('admin.products.export'))->streamedContent();

    expect($csv)
        ->toContain('SKU')
        ->toContain('Quantity')
        ->toContain('Discount Price')
        ->toContain('Product Type')
        ->toContain('Charge Shipping')
        ->toContain('Upcoming')
        ->toContain('Brand')
        ->toContain('Vendor')
        ->toContain('Excerpt (EN)')
        ->toContain('Description (BN)')
        ->toContain('LAP-1')
        ->toContain('1200.5')
        ->toContain('999.99')
        ->toContain('digital')
        ->toContain('Acme')
        ->toContain('Acme Supplies')
        ->toContain('Short summary')
        ->toContain('বিস্তারিত বর্ণনা');
});

it('exports an import template containing only the header row', function () {
    Product::factory()->create(['name' => ['en' => 'Template Must Not Appear', 'bn' => '']]);

    $csv = $this->get(route('admin.products.export', ['template' => 1]))->streamedContent();

    expect($csv)
        ->toContain('Name (EN)')
        ->toContain('SKU')
        ->toContain('Description (BN)')
        ->not->toContain('Template Must Not Appear')
        ->and(substr_count(trim($csv), "\n"))->toBe(0);
});

it('is blocked when the products feature is disabled', function () {
    Feature::create(['key' => 'products', 'label' => 'Products', 'is_enabled' => false]);

    $this->get(route('admin.products.export'))->assertNotFound();
});
