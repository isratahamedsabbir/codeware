<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name'    => fake()->name(),
            'phone_number'  => '+880' . fake()->numerify('17########'),
            'email'        => fake()->safeEmail(),
            'subject'      => fake()->sentence(4),
            'message'      => fake()->paragraphs(2, true),
            'status'       => 'unread',
        ];
    }

    public function read(): static
    {
        return $this->state(['status' => 'read']);
    }
}
