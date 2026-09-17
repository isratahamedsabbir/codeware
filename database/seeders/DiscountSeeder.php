<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        Discount::query()->delete();

        $discounts = [
            ['name' => 'Winter Sale 20%', 'type' => 'percentage', 'value' => 20, 'starts_at' => now()->subDays(5), 'ends_at' => now()->addDays(25), 'status' => 'active'],
            ['name' => 'Flash Deal - 500 off', 'type' => 'fixed', 'value' => 500, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(3), 'status' => 'active'],
            ['name' => 'New In - 15% Off', 'type' => 'percentage', 'value' => 15, 'starts_at' => now()->addDays(7), 'ends_at' => now()->addMonths(2), 'status' => 'active'],
            ['name' => 'Clearance - 40% Off', 'type' => 'percentage', 'value' => 40, 'starts_at' => now()->subDays(30), 'ends_at' => now()->subDay(), 'status' => 'inactive'],
        ];

        foreach ($discounts as $discount) {
            /** @var Discount $model */
            $model = Discount::create($discount);

            // Attach a handful of random products to each discount so the admin's
            // "Applies To" list and the dashboard product counts show real data.
            $model->products()->sync(
                Product::query()->inRandomOrder()->limit(3)->pluck('id')
            );
        }
    }
}
