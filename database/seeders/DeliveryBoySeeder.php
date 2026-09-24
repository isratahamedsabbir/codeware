<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeliveryBoySeeder extends Seeder
{
    /**
     * Seeds a demo Delivery Portal account — a customer account with the
     * 'delivery_boy' role (see access-delivery-portal gate) — and hands it a
     * few undelivered orders so the portal has something to show. Safe to
     * re-run: the account is upserted and only unassigned orders are taken.
     */
    public function run(): void
    {
        $rider = User::updateOrCreate(
            ['email' => 'deliveryboy@admin.com'],
            [
                'name' => 'Delivery Boy',
                'email' => 'deliveryboy@admin.com',
                'password' => '12345678',
                'email_verified_at' => now(),
            ]
        );

        $rider->syncRoles(['customer', User::DELIVERY_ROLE]);

        Order::whereNull('delivery_boy_id')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->oldest()
            ->take(3)
            ->update(['delivery_boy_id' => $rider->id]);
    }
}
