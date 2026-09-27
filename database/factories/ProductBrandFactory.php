<?php

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBrandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ['en' => ucfirst(fake()->unique()->word()), 'bn' => ''],
            'logo' => null,
            'type_id' => Type::idFor(Type::PRODUCT),
            'status' => 'active',
            'sort_order' => 0,
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

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
