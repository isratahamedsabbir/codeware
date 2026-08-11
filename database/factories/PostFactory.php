<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(6, false);
        $puckContent = ['root' => ['props' => []], 'content' => [], 'zones' => []];
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'title' => ['en' => $title, 'bn' => $title],
            'slug' => Str::slug($title),
            'description' => ['en' => fake()->paragraph(), 'bn' => fake()->paragraph()],
            'content' => ['en' => $puckContent, 'bn' => $puckContent],
            'featured_image' => null,
            'status' => 'inactive',
            'published_at' => null,
            'reading_time' => 1,
            'seo_title' => ['en' => null, 'bn' => null],
            'seo_description' => ['en' => null, 'bn' => null],
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'active', 'published_at' => now()]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'inactive', 'published_at' => null]);
    }

    public function active(): static
    {
        return $this->state(['status' => 'active', 'published_at' => now()]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive', 'published_at' => null]);
    }
}
