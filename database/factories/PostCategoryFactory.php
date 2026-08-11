<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        return [
            'name' => ['en' => ucwords($name), 'bn' => ucwords($name)],
            'slug' => Str::slug($name),
            'description' => ['en' => fake()->sentence(), 'bn' => fake()->sentence()],
            'status' => 'active',
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
