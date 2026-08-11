<?php

use App\Models\District;
use App\Models\Upazila;

it('filters upazilas by district_id', function () {
    $district = District::factory()->create();
    Upazila::factory()->count(2)->create(['district_id' => $district]);
    Upazila::factory()->create();

    $this->getJson('/api/v1/upazilas?district_id=' . $district->id)
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
