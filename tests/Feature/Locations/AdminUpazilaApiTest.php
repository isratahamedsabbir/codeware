<?php

use App\Models\District;
use App\Models\Upazila;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lists upazilas with pagination', function () {
    Upazila::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/upazilas?per_page=2')
        ->assertOk()
        ->assertJsonPath('meta.total', 3);
});

it('shows a single upazila', function () {
    $upazila = Upazila::factory()->create();

    $this->actingAs($this->admin)
        ->getJson("/api/v1/admin/upazilas/{$upazila->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $upazila->id);
});

it('creates an upazila', function () {
    $district = District::factory()->create();

    $payload = [
        'district_id' => $district->id,
        'name'        => ['en' => 'Tejgaon', 'bn' => 'তেজগাঁও'],
        'sort_order'  => 1,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/upazilas', $payload)
        ->assertCreated();

    $this->assertDatabaseHas('upazilas', ['district_id' => $district->id, 'sort_order' => 1]);
});

it('updates an upazila', function () {
    $upazila = Upazila::factory()->create();

    $this->actingAs($this->admin)
        ->putJson("/api/v1/admin/upazilas/{$upazila->id}", [
            'name' => ['en' => 'Updated', 'bn' => ''],
        ])
        ->assertOk();

    expect($upazila->fresh()->getTranslation('name', 'en'))->toBe('Updated');
});

it('deletes an upazila', function () {
    $upazila = Upazila::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/admin/upazilas/{$upazila->id}")
        ->assertNoContent();

    expect(Upazila::find($upazila->id))->toBeNull();
});

it('requires admin authentication', function () {
    $this->getJson('/api/v1/admin/upazilas')->assertUnauthorized();
});

it('filters upazilas by district', function () {
    $district1 = District::factory()->create();
    $district2 = District::factory()->create();
    Upazila::factory()->count(2)->create(['district_id' => $district1]);
    Upazila::factory()->create(['district_id' => $district2]);

    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/upazilas');

    $response->assertOk();
    expect(count($response->json('data')))->toBe(3);
});
