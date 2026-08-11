<?php

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

class UpazilaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'name'        => ['en' => fake()->unique()->city(), 'bn' => fake()->unique()->city()],
            'status'      => 'active',
            'sort_order'  => 0,
        ];
    }
}
