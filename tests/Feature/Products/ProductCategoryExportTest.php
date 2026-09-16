<?php

use App\Models\Feature;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports only the requested category ids as a downloadable csv', function () {
    $included = ProductCategory::factory()->create(['name' => ['en' => 'Electronics', 'bn' => '']]);
    $excluded = ProductCategory::factory()->create(['name' => ['en' => 'Groceries', 'bn' => '']]);

    $response = $this->get(route('admin.product-categories.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Name (EN)')
        ->and($csv)->toContain('Electronics')
        ->and($csv)->not->toContain('Groceries');
});

it('exports nothing but the header row when no ids are given', function () {
    ProductCategory::factory()->create(['name' => ['en' => 'Electronics', 'bn' => '']]);

    $csv = $this->get(route('admin.product-categories.export'))->streamedContent();

    expect($csv)->toContain('Name (EN)')
        ->and($csv)->not->toContain('Electronics');
});

it('is blocked when the products feature is disabled', function () {
    Feature::create(['key' => 'products', 'label' => 'Products', 'is_enabled' => false]);

    $this->get(route('admin.product-categories.export'))->assertNotFound();
});
