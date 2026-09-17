<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VoucherFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true)).' Gift Voucher';
        $value = fake()->randomElement([500, 1000, 2000, 5000]);

        return [
            'name' => ['en' => $name, 'bn' => ''],
            'description' => ['en' => fake()->sentence(), 'bn' => ''],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'price' => $value,
            'value' => $value,
            'currency' => 'BDT',
            'valid_days' => 365,
            'status' => 'active',
            'sort_order' => 0,
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
