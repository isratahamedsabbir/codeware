<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'commentable_type' => Post::class,
            'commentable_id' => Post::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->sentence(),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }
}
