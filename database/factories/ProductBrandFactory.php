<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBrandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
            'logo' => null,
            'status' => 'active',
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
