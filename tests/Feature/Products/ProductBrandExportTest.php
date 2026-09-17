<?php

use App\Models\ProductBrand;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports only the requested brand ids as a downloadable csv', function () {
    $included = ProductBrand::factory()->create(['name' => ['en' => 'Included Brand', 'bn' => '']]);
    $excluded = ProductBrand::factory()->create(['name' => ['en' => 'Excluded Brand', 'bn' => '']]);

    $response = $this->get(route('admin.product-brands.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Brand')
        ->and($csv)->not->toContain('Excluded Brand');
});

it('exports nothing but the header row when no ids are given', function () {
    ProductBrand::factory()->create(['name' => ['en' => 'Included Brand', 'bn' => '']]);

    $csv = $this->get(route('admin.product-brands.export'))->streamedContent();

    expect($csv)->toContain('Name')
        ->and($csv)->not->toContain('Included Brand');
});
