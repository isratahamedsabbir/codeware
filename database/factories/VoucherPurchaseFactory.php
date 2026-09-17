<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherPurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('01#########'),
            'recipient_name' => null,
            'message' => null,
            'price_paid' => 1000,
            'value' => 1000,
            'currency' => 'BDT',
            'status' => 'issued',
        ];
    }
}
