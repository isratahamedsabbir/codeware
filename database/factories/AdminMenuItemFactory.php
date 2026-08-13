<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AdminMenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'is_group' => false,
            'label' => fake()->unique()->words(2, true),
            'icon' => 'link',
            'route_name' => 'admin.dashboard',
            'url' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function group(): static
    {
        return $this->state([
            'is_group' => true,
            'route_name' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
