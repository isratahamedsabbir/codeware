<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'type' => Category::TYPE_PRODUCT,
            'parent_id' => null,
            'name' => ['en' => ucfirst($name), 'bn' => ucfirst($name)],
            'description' => null,
            'icon' => null,
            'sort_order' => 0,
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

    public function product(): static
    {
        return $this->state(['type' => Category::TYPE_PRODUCT]);
    }

    public function post(): static
    {
        return $this->state(['type' => Category::TYPE_POST]);
    }
}
