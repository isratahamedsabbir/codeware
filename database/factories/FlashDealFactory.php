<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FlashDealFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(3, true)),
            'type' => 'percentage',
            'value' => fake()->randomFloat(2, 5, 50),
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function expired(): static
    {
        return $this->state(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);
    }

    public function upcoming(): static
    {
        return $this->state(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
    }
}
