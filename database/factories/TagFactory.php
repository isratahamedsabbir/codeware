<?php

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['en' => $name, 'bn' => $name],
            'type_id' => Type::idFor(Type::PRODUCT),
            'status' => 'active',
        ];
    }

    public function post(): static
    {
        return $this->state(['type_id' => Type::idFor(Type::POST)]);
    }

    public function product(): static
    {
        return $this->state(['type_id' => Type::idFor(Type::PRODUCT)]);
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
