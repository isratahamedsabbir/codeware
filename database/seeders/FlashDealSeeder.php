<?php

namespace Database\Seeders;

use App\Models\FlashDeal;
use App\Models\Product;
use Illuminate\Database\Seeder;

class FlashDealSeeder extends Seeder
{
    public function run(): void
    {
        FlashDeal::query()->delete();

        // Both are live now, so the storefront's Flash Deals button and page show
        // something straight after seeding.
        $deals = [
            ['name' => 'Weekend Flash Sale', 'type' => 'percentage', 'value' => 25, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDays(2), 'status' => 'active'],
            ['name' => 'Mega Deal - 300 off', 'type' => 'fixed', 'value' => 300, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDays(5), 'status' => 'active'],
        ];

        foreach ($deals as $deal) {
            FlashDeal::create($deal)->products()->sync(
                Product::query()->active()->inRandomOrder()->limit(4)->pluck('id')
            );
        }
    }
}
