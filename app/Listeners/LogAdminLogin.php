<?php

namespace App\Listeners;

use App\Support\AdminActivity;
use Illuminate\Auth\Events\Login;

class LogAdminLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (AdminActivity::isTracked($user)) {
            AdminActivity::log(
                action: 'login',
                description: 'Signed in',
                userId: $user->id,
            );
        }
    }
}
