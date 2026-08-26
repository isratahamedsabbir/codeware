<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::query()->delete();

        $coupons = [
            ['code' => 'WELCOME10', 'type' => 'percentage', 'value' => 10, 'min_order_amount' => null, 'max_uses' => null, 'used_count' => 34, 'expires_at' => null, 'status' => 'active'],
            ['code' => 'SAVE20', 'type' => 'percentage', 'value' => 20, 'min_order_amount' => 1000, 'max_uses' => 100, 'used_count' => 62, 'expires_at' => now()->addDays(30), 'status' => 'active'],
            ['code' => 'FLAT500', 'type' => 'fixed', 'value' => 500, 'min_order_amount' => 3000, 'max_uses' => 50, 'used_count' => 12, 'expires_at' => now()->addDays(14), 'status' => 'active'],
            ['code' => 'EID25', 'type' => 'percentage', 'value' => 25, 'min_order_amount' => 2000, 'max_uses' => 200, 'used_count' => 200, 'expires_at' => now()->addDays(7), 'status' => 'active'],
            ['code' => 'FREESHIP', 'type' => 'fixed', 'value' => 120, 'min_order_amount' => null, 'max_uses' => null, 'used_count' => 89, 'expires_at' => null, 'status' => 'active'],
            ['code' => 'SUMMER15', 'type' => 'percentage', 'value' => 15, 'min_order_amount' => 1500, 'max_uses' => 80, 'used_count' => 80, 'expires_at' => now()->subDays(10), 'status' => 'inactive'],
            ['code' => 'TESTCODE', 'type' => 'fixed', 'value' => 100, 'min_order_amount' => null, 'max_uses' => 10, 'used_count' => 0, 'expires_at' => now()->addDays(60), 'status' => 'inactive'],
        ];

        foreach ($coupons as $coupon) {
            Coupon::create($coupon);
        }
    }
}
