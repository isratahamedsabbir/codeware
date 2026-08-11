<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DealerFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();
        return [
            'name'       => ['en' => $name, 'bn' => $name],
            'address'    => ['en' => fake()->address(), 'bn' => fake()->address()],
            'district'   => fake()->randomElement(['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi', 'Khulna']),
            'upazila'    => fake()->city(),
            'phone'      => '+880' . fake()->numerify('17########'),
            'email'      => fake()->optional()->safeEmail(),
            'latitude'   => fake()->latitude(20.5, 26.6),
            'longitude'  => fake()->longitude(88.0, 92.7),
            'status'     => 'active',
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
