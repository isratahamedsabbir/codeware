<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => 'cod',
            'amount' => fake()->randomFloat(2, 200, 10000),
            'currency' => 'BDT',
            'status' => 'pending',
            'meta' => null,
            'paid_at' => null,
        ];
    }

    public function status(string $status): static
    {
        return $this->state([
            'status' => $status,
            'paid_at' => $status === 'success' ? now() : null,
        ]);
    }
}
