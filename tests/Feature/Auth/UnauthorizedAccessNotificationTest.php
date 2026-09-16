<?php

use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

it('notifies admins when a user without permission hits an admin route', function () {
    Notification::fake();

    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->admin()->create();
    $intruder = User::factory()->create(['email' => 'intruder@example.com']);

    $this->actingAs($intruder)->get('/admin/posts')->assertForbidden();

    Notification::assertSentTo($admin, AdminAlert::class, function (AdminAlert $notification) {
        return str_contains($notification->message, 'intruder@example.com')
            && str_contains($notification->message, 'admin/posts');
    });
});

it('does not notify admins again for the same user+route within the debounce window', function () {
    Notification::fake();

    Role::findOrCreate('admin', 'web');
    User::factory()->admin()->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get('/admin/posts')->assertForbidden();
    $this->actingAs($intruder)->get('/admin/posts')->assertForbidden();

    Notification::assertSentToTimes(User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first(), AdminAlert::class, 1);
});

it('does not notify admins when a user with permission visits an admin route', function () {
    Notification::fake();

    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/posts')->assertOk();

    Notification::assertNothingSent();
});
