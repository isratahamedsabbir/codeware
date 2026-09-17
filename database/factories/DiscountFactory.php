<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(3, true)),
            'type' => fake()->randomElement(['percentage', 'fixed']),
            'value' => fake()->randomFloat(2, 5, 50),
            'starts_at' => fake()->boolean(50) ? now()->subDays(fake()->numberBetween(1, 30)) : null,
            'ends_at' => fake()->boolean(60) ? now()->addDays(fake()->numberBetween(7, 90)) : null,
            'status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function expired(): static
    {
        return $this->state(['ends_at' => now()->subDay()]);
    }
}
