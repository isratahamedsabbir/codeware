<?php

use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\Notification;

it('notifies admins when a user without permission hits an admin route', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $intruder = User::factory()->create(['is_admin' => false, 'email' => 'intruder@example.com']);

    $this->actingAs($intruder)->get('/admin/posts')->assertForbidden();

    Notification::assertSentTo($admin, AdminAlert::class, function (AdminAlert $notification) {
        return str_contains($notification->message, 'intruder@example.com')
            && str_contains($notification->message, 'admin/posts');
    });
});

it('does not notify admins again for the same user+route within the debounce window', function () {
    Notification::fake();

    User::factory()->create(['is_admin' => true]);
    $intruder = User::factory()->create(['is_admin' => false]);

    $this->actingAs($intruder)->get('/admin/posts')->assertForbidden();
    $this->actingAs($intruder)->get('/admin/posts')->assertForbidden();

    Notification::assertSentToTimes(User::where('is_admin', true)->first(), AdminAlert::class, 1);
});

it('does not notify admins when a user with permission visits an admin route', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin/posts')->assertOk();

    Notification::assertNothingSent();
});
