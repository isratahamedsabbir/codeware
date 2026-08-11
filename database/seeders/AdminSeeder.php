<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Seed the application's admin and editor users.
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

        $editor = User::updateOrCreate(
            ['email' => 'editor@admin.com'],
            [
                'name' => 'Editor',
                'email' => 'editor@admin.com',
                'password' => '12345678',
                'email_verified_at' => now(),
                'is_admin' => false,
            ]
        );

        $editor->assignRole('editor');
    }
}
