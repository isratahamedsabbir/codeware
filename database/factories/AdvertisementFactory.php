<?php

namespace Database\Factories;

use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdvertisementFactory extends Factory
{
    protected $model = Advertisement::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(4, true)),
            'image' => '/storage/demo.jpg',
            'url' => fake()->url(),
            'clicks' => 0,
            'valid_from' => now()->subDays(1)->startOfDay(),
            'valid_until' => now()->addDays(30)->endOfDay(),
        ];
    }

    public function alwaysOn(): static
    {
        return $this->state(fn () => ['valid_from' => null, 'valid_until' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['valid_from' => now()->addDays(2)->startOfDay(), 'valid_until' => now()->addDays(10)->endOfDay()]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['valid_from' => now()->subDays(10)->startOfDay(), 'valid_until' => now()->subDays(2)->endOfDay()]);
    }
}
