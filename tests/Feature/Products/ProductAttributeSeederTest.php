<?php

use App\Models\ProductAttribute;
use App\Models\User;
use Database\Seeders\ProductAttributeSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    User::factory()->admin()->create();
});

it('seeds dummy product attributes with predefined values', function () {
    $this->seed(ProductAttributeSeeder::class);

    $names = ProductAttribute::pluck('name')->all();

    expect($names)->toContain('Color', 'Size', 'Material', 'Storage');

    $color = ProductAttribute::where('name', 'Color')->first();
    expect($color->values)->toContain('Red', 'Blue', 'Black', 'White', 'Silver', 'Gold');

    $size = ProductAttribute::where('name', 'Size')->first();
    expect($size->values)->toContain('S', 'M', 'L', 'XL', 'XXL');
});

it('does not duplicate attribute rows when re-run', function () {
    $this->seed(ProductAttributeSeeder::class);
    $this->seed(ProductAttributeSeeder::class);

    expect(ProductAttribute::where('name', 'Color')->count())->toBe(1);

    // A seeder-run attribute that was later edited keeps its values — the
    // seeder skips existing names entirely rather than overwriting them.
    $size = ProductAttribute::where('name', 'Size')->first();
    $size->update(['values' => ['S', 'M']]);

    $this->seed(ProductAttributeSeeder::class);

    expect(ProductAttribute::where('name', 'Size')->value('values'))->toBe(['S', 'M']);
});
