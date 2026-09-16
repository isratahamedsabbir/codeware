<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 100, 3000);
        $quantity = fake()->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'service_id' => null,
            'type' => 'product',
            'item_name' => fake()->words(3, true),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $unitPrice * $quantity,
        ];
    }

    public function forService(): static
    {
        return $this->state(fn () => [
            'product_id' => null,
            'service_id' => Service::factory(),
            'type' => 'service',
        ]);
    }
}
