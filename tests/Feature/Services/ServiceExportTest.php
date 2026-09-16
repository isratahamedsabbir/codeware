<?php

use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports only the requested service ids as a downloadable csv', function () {
    $included = Service::factory()->create(['name' => ['en' => 'Included Service', 'bn' => '']]);
    $excluded = Service::factory()->create(['name' => ['en' => 'Excluded Service', 'bn' => '']]);

    $response = $this->get(route('admin.services.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Service')
        ->and($csv)->not->toContain('Excluded Service');
});

it('exports nothing but the header row when no ids are given', function () {
    Service::factory()->create(['name' => ['en' => 'Included Service', 'bn' => '']]);

    $csv = $this->get(route('admin.services.export'))->streamedContent();

    expect($csv)->toContain('Name (EN)')
        ->and($csv)->not->toContain('Included Service');
});
