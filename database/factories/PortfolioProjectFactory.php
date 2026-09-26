<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioProjectFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'title' => ['en' => ucfirst($title), 'bn' => ucfirst($title)],
            'description' => ['en' => fake()->paragraph(), 'bn' => fake()->paragraph()],
            'icon' => '🚀',
            'tech' => fake()->randomElements(['Laravel', 'Livewire', 'Redis', 'MySQL', 'Tailwind CSS']),
            'stats' => 'Open Source',
            'link' => fake()->url(),
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
