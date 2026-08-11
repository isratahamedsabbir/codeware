<?php

use App\Models\District;

it('returns paginated districts', function () {
    District::factory()->count(3)->create();

    $this->getJson('/api/v1/districts?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 3);
});

it('returns district detail with upazilas', function () {
    $district = District::factory()->hasUpazilas(2)->create();

    $this->getJson("/api/v1/districts/{$district->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $district->id)
        ->assertJsonCount(2, 'data.upazilas');
});
