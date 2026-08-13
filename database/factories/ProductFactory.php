<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'product_category_id' => ProductCategory::factory(),
            'name' => ['en' => ucfirst($name), 'bn' => ucfirst($name)],
            'slug' => Str::slug($name),
            'description' => ['en' => fake()->paragraph(), 'bn' => fake()->paragraph()],
            'featured_image' => null,
            'status' => 'inactive',
            'is_featured' => false,
            'sort_order' => 0,
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

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
