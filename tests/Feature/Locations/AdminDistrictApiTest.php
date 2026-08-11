<?php

use App\Models\District;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lists districts with pagination', function () {
    District::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/districts?per_page=2')
        ->assertOk()
        ->assertJsonPath('meta.total', 3);
});

it('shows a single district', function () {
    $district = District::factory()->create();

    $this->actingAs($this->admin)
        ->getJson("/api/v1/admin/districts/{$district->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $district->id);
});

it('creates a district', function () {
    $payload = [
        'name'       => ['en' => 'Dhaka', 'bn' => 'ঢাকা'],
        'sort_order' => 1,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/districts', $payload)
        ->assertCreated();

    $this->assertDatabaseHas('districts', ['sort_order' => 1]);
});

it('updates a district', function () {
    $district = District::factory()->create();

    $this->actingAs($this->admin)
        ->putJson("/api/v1/admin/districts/{$district->id}", [
            'name' => ['en' => 'Updated', 'bn' => ''],
        ])
        ->assertOk();

    expect($district->fresh()->getTranslation('name', 'en'))->toBe('Updated');
});

it('deletes a district', function () {
    $district = District::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/admin/districts/{$district->id}")
        ->assertNoContent();

    expect(District::find($district->id))->toBeNull();
});

it('requires admin authentication', function () {
    $this->getJson('/api/v1/admin/districts')->assertUnauthorized();
});
