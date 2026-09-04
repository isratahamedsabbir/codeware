<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentGatewayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(1),
            'name' => fake()->word(),
            'is_enabled' => false,
            'mode' => 'sandbox',
            'credentials' => [],
            'sort_order' => 0,
        ];
    }
}
