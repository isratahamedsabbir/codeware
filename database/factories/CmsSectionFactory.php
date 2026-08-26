<?php

namespace Database\Factories;

use App\Models\CmsSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CmsSection>
 */
class CmsSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'page' => 'home',
            'section' => fake()->unique()->word(),
            'titles' => [['en' => fake()->sentence(3), 'bn' => '']],
            'descriptions' => [['en' => fake()->sentence(10), 'bn' => '']],
            'buttons' => [],
            'cards' => [],
            'images' => [],
            'bg_image' => null,
            'status' => 'active',
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
