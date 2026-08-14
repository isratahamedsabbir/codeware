<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 200, 10000);

        return [
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('01#########'),
            'shipping_address' => fake()->address(),
            'status' => fake()->randomElement(Order::STATUSES),
            'payment_method' => 'cod',
            'payment_status' => fake()->randomElement(Order::PAYMENT_STATUSES),
            'currency' => 'BDT',
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'notes' => null,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function paymentMethod(string $method): static
    {
        return $this->state(['payment_method' => $method]);
    }

    public function paymentStatus(string $status): static
    {
        return $this->state(['payment_status' => $status]);
    }
}
