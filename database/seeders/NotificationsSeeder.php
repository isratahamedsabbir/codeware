<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

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

        Notification::send($admins, new AdminAlert(
            'Welcome to the admin panel',
            'This is where you will see updates about contacts, orders and system events.',
            route('admin.dashboard'),
        ));

        Notification::send($admins, new AdminAlert(
            'New contact message',
            'Sample visitor: Please call me about pricing.',
            route('admin.contacts'),
        ));
    }
}
