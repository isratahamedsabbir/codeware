<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('????##')),
            'type' => fake()->randomElement(['percentage', 'fixed']),
            'value' => fake()->randomFloat(2, 5, 50),
            'min_order_amount' => fake()->boolean(40) ? fake()->randomFloat(2, 500, 2000) : null,
            'max_uses' => fake()->boolean(50) ? fake()->numberBetween(10, 200) : null,
            'used_count' => 0,
            'expires_at' => fake()->boolean(50) ? fake()->dateTimeBetween('now', '+90 days') : null,
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
        return $this->state(['expires_at' => fake()->dateTimeBetween('-30 days', '-1 day')]);
    }
}
