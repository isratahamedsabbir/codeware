<?php

use App\Models\Dealer;
use App\Models\ProductCategory;

it('returns only active dealers on public listing', function () {
    Dealer::factory()->create(['name' => ['en' => 'Active One', 'bn' => '']]);
    Dealer::factory()->inactive()->create(['name' => ['en' => 'Hidden', 'bn' => '']]);

    $this->getJson('/api/v1/dealers')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active One');
});

it('returns paginated dealers with meta', function () {
    Dealer::factory()->count(5)->create();

    $this->getJson('/api/v1/dealers?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.per_page', 2);
});

it('filters dealers by district', function () {
    Dealer::factory()->create(['district' => 'Dhaka']);
    Dealer::factory()->create(['district' => 'Chattogram']);

    $this->getJson('/api/v1/dealers?district=Dhaka')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters dealers by upazila', function () {
    Dealer::factory()->create(['upazila' => 'Tejgaon']);
    Dealer::factory()->create(['upazila' => 'Mirpur']);

    $this->getJson('/api/v1/dealers?upazila=Tejgaon')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters dealers by product category slug', function () {
    $cat = ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    $dealer = Dealer::factory()->create();
    $dealer->categories()->attach($cat->id);
    Dealer::factory()->create();

    $this->getJson("/api/v1/dealers?category={$cat->slug}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('searches dealers by name', function () {
    Dealer::factory()->create(['name' => ['en' => 'Dhaka Agro Store', 'bn' => '']]);
    Dealer::factory()->create(['name' => ['en' => 'Rajshahi Seeds', 'bn' => '']]);

    $this->getJson('/api/v1/dealers?search=Agro')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Dhaka Agro Store');
});

it('returns dealer detail by slug with categories', function () {
    $cat = ProductCategory::factory()->create();
    $dealer = Dealer::factory()->create(['name' => ['en' => 'Test Dealer', 'bn' => '']]);
    $dealer->categories()->attach($cat->id);

    $this->getJson("/api/v1/dealers/{$dealer->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $dealer->slug)
        ->assertJsonStructure(['data' => [
            'id', 'name', 'slug', 'address', 'district', 'upazila',
            'phone', 'email', 'latitude', 'longitude', 'status',
            'sort_order', 'distance_km', 'categories',
        ]])
        ->assertJsonCount(1, 'data.categories');
});

it('returns 404 for inactive dealer on public endpoint', function () {
    $dealer = Dealer::factory()->inactive()->create();

    $this->getJson("/api/v1/dealers/{$dealer->slug}")->assertNotFound();
});

it('returns nearby dealers with distance_km when lat lng provided', function () {
    // Dealer in Dhaka area
    Dealer::factory()->create(['latitude' => 23.7465, 'longitude' => 90.3760]);
    // Dealer in Chittagong — outside 50km radius from reference point
    Dealer::factory()->create(['latitude' => 22.3569, 'longitude' => 91.7832]);

    $response = $this->getJson('/api/v1/dealers?lat=23.8103&lng=90.4125&radius=50');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.distance_km'))->not->toBeNull();
});

it('nearby search orders dealers by distance ascending', function () {
    Dealer::factory()->create([
        'name'      => ['en' => 'Close Dealer', 'bn' => ''],
        'latitude'  => 23.7503,
        'longitude' => 90.3750,
    ]);
    Dealer::factory()->create([
        'name'      => ['en' => 'Far Dealer', 'bn' => ''],
        'latitude'  => 23.5700,
        'longitude' => 90.2800,
    ]);

    $response = $this->getJson('/api/v1/dealers?lat=23.7465&lng=90.3760&radius=100');

    $response->assertOk();
    expect($response->json('data.0.name'))->toBe('Close Dealer');
    expect($response->json('data.1.name'))->toBe('Far Dealer');
});

it('distance_km is null when no lat lng provided', function () {
    Dealer::factory()->create();

    $response = $this->getJson('/api/v1/dealers');

    $response->assertOk();
    expect($response->json('data.0.distance_km'))->toBeNull();
});

it('returns dealer name in bn locale', function () {
    Dealer::factory()->create(['name' => ['en' => 'English Name', 'bn' => 'বাংলা নাম']]);

    $this->getJson('/api/v1/dealers?locale=bn')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'বাংলা নাম');
});
