<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Seed a demo user for each of the three admin tiers: Super Admin
     * (is_admin=true, an unconditional bypass everywhere), Admin (the
     * 'admin' Spatie role, which has every permission), and Staff (the
     * 'staff' role, content-only — see RolePermissionSeeder). Also seeds a
     * demo Vendor Portal account — the 'vendor' role alone doesn't grant
     * portal access yet, an admin still has to assign it an actual vendor
     * from Users → edit → Vendor Access (see access-vendor-portal gate).
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => '12345678',
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );

        $admin->assignRole('admin');

        $staff = User::updateOrCreate(
            ['email' => 'staff@admin.com'],
            [
                'name' => 'Staff',
                'email' => 'staff@admin.com',
                'password' => '12345678',
                'email_verified_at' => now(),
                'is_admin' => false,
            ]
        );

        $staff->assignRole('staff');

        $vendor = User::updateOrCreate(
            ['email' => 'vendor@admin.com'],
            [
                'name' => 'Vendor',
                'email' => 'vendor@admin.com',
                'password' => '12345678',
                'email_verified_at' => now(),
                'is_admin' => false,
            ]
        );

        $vendor->assignRole('vendor');
    }
}
