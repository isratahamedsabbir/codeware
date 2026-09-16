<?php

use App\Models\Language;
use App\Models\Service;

it('lists only active services, ordered by sort_order', function () {
    Service::factory()->published()->create(['name' => ['en' => 'Second', 'bn' => ''], 'sort_order' => 2]);
    Service::factory()->published()->create(['name' => ['en' => 'First', 'bn' => ''], 'sort_order' => 1]);
    Service::factory()->draft()->create(['name' => ['en' => 'Hidden', 'bn' => '']]);

    $response = $this->getJson('/api/v1/services');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'First')
        ->assertJsonMissingPath('data.2');
});

it('services response includes expected fields and the {data, meta} envelope', function () {
    Service::factory()->published()->create();

    $this->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'slug', 'name', 'price', 'featured_image']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('returns a translated name for the requested locale', function () {
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    Service::factory()->published()->create(['name' => ['en' => 'Consulting', 'bn' => 'পরামর্শ']]);

    $this->getJson('/api/v1/services?locale=bn')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'পরামর্শ');
});

it('returns a single service by slug with its description', function () {
    Service::factory()->published()->create([
        'name' => ['en' => 'Consulting', 'bn' => ''],
        'slug' => 'consulting',
        'description' => ['en' => 'One hour of consulting.', 'bn' => ''],
    ]);

    $this->getJson('/api/v1/services/consulting')
        ->assertOk()
        ->assertJsonPath('data.slug', 'consulting')
        ->assertJsonPath('data.description', 'One hour of consulting.');
});

it('404s an inactive service looked up by slug', function () {
    Service::factory()->draft()->create(['slug' => 'hidden-service']);

    $this->getJson('/api/v1/services/hidden-service')->assertNotFound();
});
