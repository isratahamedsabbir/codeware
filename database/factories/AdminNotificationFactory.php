<?php

namespace Database\Factories;

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminNotification>
 */
class AdminNotificationFactory extends Factory
{
    protected $model = AdminNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'message' => fake()->sentence(),
            'link' => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
