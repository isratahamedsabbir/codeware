<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'status' => 'subscribed',
        ];
    }

    public function unsubscribed(): static
    {
        return $this->state(['status' => 'unsubscribed']);
    }
}
