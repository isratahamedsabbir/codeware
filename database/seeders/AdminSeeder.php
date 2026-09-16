<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Seed a demo user for each of the three admin tiers: Super Admin
     * (is_admin=true, an unconditional bypass everywhere), Admin (the
     * 'admin' Spatie role, which has every permission), and Staff (the
     * 'staff' role, content-only — see RolePermissionSeeder). Also seeds a
     * demo Vendor Portal account — the 'vendor' role alone doesn't grant
     * portal access, it also needs an assigned vendor, which VendorSeeder
     * handles (see access-vendor-portal gate).
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

        // RolePermissionSeeder runs before this one (assignRole('admin') above
        // needs the role to already exist), so when those rows were created,
        // no is_admin=true user existed yet for the created_by fallback in
        // AppServiceProvider::configureCreatorTracking() to find. Backfill
        // them now that the admin does.
        Role::whereNull('created_by')->update(['created_by' => $admin->id]);
        Permission::whereNull('created_by')->update(['created_by' => $admin->id]);

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
