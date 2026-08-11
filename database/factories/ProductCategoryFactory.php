<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        return [
            'name'        => ['en' => ucfirst($name), 'bn' => ucfirst($name)],
            'description' => ['en' => fake()->sentence(), 'bn' => fake()->sentence()],
            'icon'        => null,
            'sort_order'  => 0,
        ];
    }
}
