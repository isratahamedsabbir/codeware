<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DistrictFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city();
        return [
            'name'       => ['en' => $name, 'bn' => $name],
            'status'     => 'active',
            'sort_order' => 0,
        ];
    }
}
