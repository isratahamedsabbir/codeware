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
     * 'staff' role, content-only — see RolePermissionSeeder).
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
    }
}
