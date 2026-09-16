<?php

use App\Models\Feature;
use App\Models\ProductVendor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports only the requested vendor ids as a downloadable csv', function () {
    $included = ProductVendor::factory()->create(['name' => 'Acme Supplies']);
    $excluded = ProductVendor::factory()->create(['name' => 'Other Supplier']);

    $response = $this->get(route('admin.product-vendors.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Name')
        ->and($csv)->toContain('Acme Supplies')
        ->and($csv)->not->toContain('Other Supplier');
});

it('exports nothing but the header row when no ids are given', function () {
    ProductVendor::factory()->create(['name' => 'Acme Supplies']);

    $csv = $this->get(route('admin.product-vendors.export'))->streamedContent();

    expect($csv)->toContain('Name')
        ->and($csv)->not->toContain('Acme Supplies');
});

it('is blocked when the products feature is disabled', function () {
    Feature::create(['key' => 'products', 'label' => 'Products', 'is_enabled' => false]);

    $this->get(route('admin.product-vendors.export'))->assertNotFound();
});
