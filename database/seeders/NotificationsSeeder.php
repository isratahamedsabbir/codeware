<?php

namespace Database\Seeders;

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationsSeeder extends Seeder
{
    /**
     * Seed a few demo notifications for admin users.
     */
    public function run(): void
    {
        $admins = User::where('is_admin', true)->get();

        if ($admins->isEmpty()) {
            return;
        }

        foreach ($admins as $admin) {
            AdminNotification::create([
                'user_id' => $admin->id,
                'title'   => 'Welcome to the admin panel',
                'message' => 'This is where you will see updates about contacts, orders and system events.',
                'link'    => route('admin.dashboard'),
            ]);

            AdminNotification::create([
                'user_id' => $admin->id,
                'title'   => 'New contact message',
                'message' => 'Sample visitor: Please call me about pricing.',
                'link'    => route('admin.contacts'),
            ]);
        }
    }
}
