<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();
        return [
            'image'      => fake()->optional()->imageUrl(),
            'name'       => ['en' => $name, 'bn' => $name],
            'comment'    => ['en' => fake()->paragraph(), 'bn' => fake()->paragraph()],
            'location'   => fake()->city(),
            'type'       => fake()->randomElement(['customer', 'partner', 'dealer']),
            'product_id' => null,
            'status'     => 'active',
            'sort_order' => 0,
        ];
    }

    public function withProduct(): static
    {
        return $this->state(['product_id' => Product::factory()]);
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
