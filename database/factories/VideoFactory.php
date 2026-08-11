<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);
        return [
            'title'        => ['en' => $title, 'bn' => $title],
            'youtube_link' => 'https://www.youtube.com/watch?v=' . fake()->regexify('[A-Za-z0-9_-]{11}'),
            'thumbnail'    => fake()->optional()->imageUrl(),
            'status'       => 'inactive',
            'sort_order'   => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
