<?php

namespace Database\Factories;

use App\Models\CmsSection;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CmsSection>
 */
class CmsSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'name' => fake()->unique()->word(),
            'sort_order' => 0,
            'cards' => [],
            'constant' => [],
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
