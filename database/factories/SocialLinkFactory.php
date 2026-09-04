<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SocialLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'platform' => fake()->unique()->slug(1),
            'label' => fake()->word(),
            'url' => '',
            'sort_order' => 0,
        ];
    }
}
