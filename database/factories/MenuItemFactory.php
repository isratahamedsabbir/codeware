<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group' => MenuItem::GROUP_ADMIN_SIDEBAR,
            'parent_id' => null,
            'is_group' => false,
            'label' => fake()->unique()->words(2, true),
            'icon' => 'link',
            'route_name' => 'admin.dashboard',
            'url' => null,
            'sort_order' => 0,
            'is_active' => true,
            'is_short_menu' => false,
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

    public function shortMenu(): static
    {
        return $this->state(['is_short_menu' => true]);
    }
}
