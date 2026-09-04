<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FeatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(1),
            'label' => fake()->word(),
            'is_enabled' => true,
        ];
    }
}
