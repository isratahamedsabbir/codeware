<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioExperienceFactory extends Factory
{
    public function definition(): array
    {
        $role = fake()->jobTitle();

        return [
            'role' => ['en' => $role, 'bn' => $role],
            'company' => ['en' => fake()->company(), 'bn' => fake()->company()],
            'period' => '2024 - Present',
            'description' => ['en' => fake()->paragraph(), 'bn' => fake()->paragraph()],
            'status' => 'inactive',
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
