<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(3, true);
        $puckContent = ['root' => ['props' => []], 'content' => [], 'zones' => []];
        return [
            'user_id' => User::factory(),
            'title' => ['en' => ucwords($title), 'bn' => ucwords($title)],
            'slug' => Str::slug($title),
            'content' => ['en' => $puckContent, 'bn' => $puckContent],
            'status' => 'inactive',
            'template' => 'puck',
            'sort_order' => 0,
            'seo_title' => ['en' => null, 'bn' => null],
            'seo_description' => ['en' => null, 'bn' => null],
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
}
