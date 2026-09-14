<?php

namespace Database\Seeders;

use App\Models\ProductVendor;
use App\Models\User;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Seed a default vendor and assign the demo vendor@admin.com account to it.
     * The 'vendor' role alone doesn't pass the access-vendor-portal gate — a
     * user also needs an assigned vendor (see User::vendors(), AdminSeeder).
     */
    public function run(): void
    {
        $vendor = ProductVendor::firstOrCreate(
            ['name' => 'Default Vendor'],
            [
                'email' => 'vendor@admin.com',
                'mobile' => '01700000000',
                'address' => 'Dhaka, Bangladesh',
                'status' => 'active',
            ],
        );

        $user = User::where('email', 'vendor@admin.com')->first();

        if ($user) {
            $vendor->users()->syncWithoutDetaching([$user->id]);
        }
    }
}
