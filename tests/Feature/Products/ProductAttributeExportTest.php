<?php

use App\Models\ProductAttribute;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports only the requested attribute ids as a downloadable csv', function () {
    $included = ProductAttribute::create(['name' => 'Color', 'values' => ['Red', 'Blue']]);
    $excluded = ProductAttribute::create(['name' => 'Size', 'values' => ['S', 'M']]);

    $response = $this->get(route('admin.product-attributes.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Color')
        ->and($csv)->toContain('Red, Blue')
        ->and($csv)->not->toContain('Size');
});

it('exports nothing but the header row when no ids are given', function () {
    ProductAttribute::create(['name' => 'Color', 'values' => ['Red']]);

    $csv = $this->get(route('admin.product-attributes.export'))->streamedContent();

    expect($csv)->toContain('Name')
        ->and($csv)->not->toContain('Color');
});
