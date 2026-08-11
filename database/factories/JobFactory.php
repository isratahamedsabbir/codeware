<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->jobTitle();
        return [
            'department_id'  => Department::factory(),
            'title'          => ['en' => $title, 'bn' => $title],
            'description'    => ['en' => fake()->paragraphs(2, true), 'bn' => null],
            'position'       => fake()->jobTitle(),
            'vacancy'        => fake()->numberBetween(1, 10),
            'deadline'       => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'location'       => fake()->randomElement(['Dhaka', 'Chattogram', 'Sylhet', 'Remote']),
            'status'         => 'inactive',
            'sort_order'     => 0,
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
