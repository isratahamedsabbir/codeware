<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioSkillFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['en' => ucfirst($name), 'bn' => ucfirst($name)],
            'group' => 'Backend',
            'icon' => '⚙️',
            'description' => ['en' => fake()->sentence(), 'bn' => fake()->sentence()],
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
