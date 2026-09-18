<?php

namespace Database\Seeders;

use App\Models\Voucher;
use App\Models\VoucherPurchase;
use Illuminate\Database\Seeder;

class VoucherPurchaseSeeder extends Seeder
{
    /**
     * Demo gift voucher sales for the admin Voucher Sales list — random buyers
     * against the vouchers VoucherSeeder just created, safe on a fresh install
     * (unlike DemoContentSeeder's random data).
     */
    public function run(): void
    {
        $vouchers = Voucher::all();

        if ($vouchers->isEmpty()) {
            return;
        }

        VoucherPurchase::query()->delete();

        collect(range(1, 40))->each(function () use ($vouchers) {
            $voucher = $vouchers->random();
            $purchasedAt = fake()->dateTimeBetween('-90 days', 'now');

            $purchase = VoucherPurchase::create([
                'voucher_id' => $voucher->id,
                'customer_name' => fake()->name(),
                'customer_email' => fake()->safeEmail(),
                'customer_phone' => fake()->numerify('01#########'),
                'recipient_name' => fake()->boolean(40) ? fake()->name() : null,
                'message' => fake()->boolean(30) ? fake()->sentence() : null,
                'price_paid' => $voucher->price,
                'value' => $voucher->value,
                'currency' => $voucher->currency,
                'purchased_at' => $purchasedAt,
                'created_at' => $purchasedAt,
            ]);

            $status = fake()->randomElement(['issued', 'issued', 'issued', 'redeemed', 'redeemed', 'expired']);

            $purchase->update([
                'status' => $status,
                // An expired demo voucher needs its expiry actually in the past,
                // independent of the voucher product's own validity window.
                'expires_at' => $status === 'expired'
                    ? fake()->dateTimeBetween('-30 days', '-1 days')
                    : $purchase->expires_at,
            ]);
        });
    }
}
