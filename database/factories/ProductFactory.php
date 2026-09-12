<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => ['en' => ucfirst($name), 'bn' => ucfirst($name)],
            'description' => ['en' => fake()->paragraph(), 'bn' => fake()->paragraph()],
            'featured_image' => null,
            'status' => 'inactive',
            'product_type' => 'physical',
            'price' => fake()->randomFloat(2, 100, 5000),
            'is_featured' => false,
            'is_upcoming' => false,
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

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function upcoming(): static
    {
        return $this->state(['is_upcoming' => true]);
    }

    public function digital(): static
    {
        return $this->state(['product_type' => 'digital']);
    }
}
