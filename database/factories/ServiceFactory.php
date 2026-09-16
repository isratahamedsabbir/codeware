<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => ['en' => ucfirst($name), 'bn' => ucfirst($name)],
            'description' => ['en' => fake()->paragraph(), 'bn' => fake()->paragraph()],
            'featured_image' => null,
            'status' => 'inactive',
            'price' => fake()->randomFloat(2, 100, 5000),
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
