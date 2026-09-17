<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['en' => $name, 'bn' => $name],
            'slug' => Str::slug($name),
            'status' => 'active',
        ];
    }

    public function post(): static
    {
        return $this->state(['type' => Tag::TYPE_POST]);
    }

    public function product(): static
    {
        return $this->state(['type' => Tag::TYPE_PRODUCT]);
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
