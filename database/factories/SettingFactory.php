<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key'       => fake()->unique()->slug(2),
            'value'     => fake()->sentence(),
            'type'      => 'string',
            'group'     => 'general',
            'is_public' => false,
        ];
    }
}
