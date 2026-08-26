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
            'title' => ['en' => fake()->sentence(3), 'bn' => ''],
            'description' => ['en' => fake()->sentence(10), 'bn' => ''],
            'buttons' => [],
            'cards' => [],
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
