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
            'title' => ['en' => fake()->sentence(3), 'bn' => ''],
            'description' => ['en' => fake()->sentence(10), 'bn' => ''],
            'cards' => [],
            'metadata' => [],
            'image' => null,
            'bg_image' => null,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
