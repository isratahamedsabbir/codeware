<?php

use App\Models\Feature;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports services as a downloadable csv with the importable fields', function () {
    Service::factory()->published()->create([
        'name' => ['en' => 'Consulting', 'bn' => 'পরামর্শ'],
        'description' => ['en' => 'English description', 'bn' => 'বাংলা বর্ণনা'],
        'slug' => 'consulting',
        'price' => 1500,
        'featured_image' => 'https://example.test/service.jpg',
        'sort_order' => 3,
    ]);

    $response = $this->get(route('admin.services.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $response->assertDownload();

    $csv = $response->streamedContent();

    expect($csv)->toContain('Name (EN)')
        ->and($csv)->toContain('Featured Image')
        ->and($csv)->toContain('Sort Order')
        ->and($csv)->toContain('Description (EN)')
        ->and($csv)->toContain('Description (BN)')
        ->and($csv)->toContain('Consulting')
        ->and($csv)->toContain('পরামর্শ')
        ->and($csv)->toContain('consulting')
        ->and($csv)->toContain('1500')
        ->and($csv)->toContain('https://example.test/service.jpg')
        ->and($csv)->toContain('English description')
        ->and($csv)->toContain('বাংলা বর্ণনা');
});

it('exports all services when no ids are given', function () {
    Service::factory()->create(['name' => ['en' => 'Included Service', 'bn' => '']]);
    Service::factory()->create(['name' => ['en' => 'Second Service', 'bn' => '']]);

    $csv = $this->get(route('admin.services.export'))->streamedContent();

    expect($csv)->toContain('Included Service')
        ->and($csv)->toContain('Second Service');
});

it('exports an import template containing only the header row', function () {
    Service::factory()->create(['name' => ['en' => 'Template Must Not Appear', 'bn' => '']]);

    $csv = $this->get(route('admin.services.export', ['template' => 1]))->streamedContent();

    expect($csv)->toContain('Name (EN)')
        ->and($csv)->toContain('Description (EN)')
        ->and($csv)->toContain('Description (BN)')
        ->and($csv)->not->toContain('Template Must Not Appear')
        ->and(substr_count(trim($csv), "\n"))->toBe(0);
});

it('only includes services matching the search filter', function () {
    Service::factory()->create(['name' => ['en' => 'Matching Service', 'bn' => '']]);
    Service::factory()->create(['name' => ['en' => 'Other Service', 'bn' => '']]);

    $csv = $this->get(route('admin.services.export', ['search' => 'Matching']))->streamedContent();

    expect($csv)->toContain('Matching Service')
        ->and($csv)->not->toContain('Other Service');
});

it('only includes services matching the status filter', function () {
    Service::factory()->published()->create(['name' => ['en' => 'Active Service', 'bn' => '']]);
    Service::factory()->draft()->create(['name' => ['en' => 'Inactive Service', 'bn' => '']]);

    $csv = $this->get(route('admin.services.export', ['status' => 'active']))->streamedContent();

    expect($csv)->toContain('Active Service')
        ->and($csv)->not->toContain('Inactive Service');
});

it('exports only the requested service ids, ignoring search and status', function () {
    $included = Service::factory()->create(['name' => ['en' => 'Selected Service', 'bn' => '']]);
    Service::factory()->create(['name' => ['en' => 'Other Service', 'bn' => '']]);

    $csv = $this->get(route('admin.services.export', [
        'ids' => [$included->id],
        'search' => 'nonexistent',
    ]))->streamedContent();

    expect($csv)->toContain('Selected Service')
        ->and($csv)->not->toContain('Other Service');
});

it('is blocked when the services feature is disabled', function () {
    Feature::create(['key' => 'services', 'label' => 'Services', 'is_enabled' => false]);

    $this->get(route('admin.services.export'))->assertNotFound();
});
