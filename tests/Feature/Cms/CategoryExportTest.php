<?php

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports only the requested category ids as a downloadable csv, including the type', function () {
    $included = Category::factory()->product()->create(['name' => ['en' => 'Included Category', 'bn' => '']]);
    $excluded = Category::factory()->post()->create(['name' => ['en' => 'Excluded Category', 'bn' => '']]);

    $response = $this->get(route('admin.categories.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Category')
        ->and($csv)->toContain('Product')
        ->and($csv)->not->toContain('Excluded Category');
});

it('exports nothing but the header row when no ids are given', function () {
    Category::factory()->create(['name' => ['en' => 'Included Category', 'bn' => '']]);

    $csv = $this->get(route('admin.categories.export'))->streamedContent();

    expect($csv)->toContain('Type')
        ->and($csv)->not->toContain('Included Category');
});
