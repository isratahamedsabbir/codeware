<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Seed the default shipping methods shown at Sales → Shipping Methods.
     * Names are unique, so re-running is safe (updateOrCreate).
     */
    public function run(): void
    {
        $methods = [
            ['name' => 'Inside Dhaka (Standard)', 'cost' => 60, 'status' => 'active'],
            ['name' => 'Outside Dhaka (Standard)', 'cost' => 120, 'status' => 'active'],
            ['name' => 'Express Delivery', 'cost' => 250, 'status' => 'active'],
            ['name' => 'Store Pickup', 'cost' => 0, 'status' => 'active'],
            ['name' => 'Free Shipping', 'cost' => 0, 'status' => 'inactive'],
        ];

        foreach ($methods as $method) {
            ShippingMethod::updateOrCreate(['name' => $method['name']], $method);
        }
    }
}
