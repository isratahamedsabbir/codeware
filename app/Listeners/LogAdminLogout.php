<?php

namespace App\Listeners;

use App\Support\AdminActivity;
use Illuminate\Auth\Events\Logout;

class LogAdminLogout
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (AdminActivity::isTracked($user)) {
            AdminActivity::log(
                action: 'logout',
                description: 'Signed out',
                userId: $user->id,
            );
        }
    }
}
