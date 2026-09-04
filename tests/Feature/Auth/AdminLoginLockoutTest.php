<?php

use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\Notification;

it('notifies admins after exactly 3 wrong-password attempts, not before', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['email' => 'victim@example.com', 'password' => 'correct-password']);

    foreach (range(1, 2) as $_) {
        $this->post('/login', ['email' => $target->email, 'password' => 'wrong-password']);
    }

    Notification::assertNothingSent();

    $this->post('/login', ['email' => $target->email, 'password' => 'wrong-password']);

    Notification::assertSentTo($admin, AdminAlert::class, function (AdminAlert $notification) use ($target) {
        return str_contains($notification->message, $target->email);
    });
});

it('does not notify admins again for further attempts within the same lockout window', function () {
    Notification::fake();

    User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['email' => 'victim2@example.com', 'password' => 'correct-password']);

    foreach (range(1, 3) as $_) {
        $this->post('/login', ['email' => $target->email, 'password' => 'wrong-password']);
    }

    Notification::assertSentToTimes(User::where('is_admin', true)->first(), AdminAlert::class, 1);

    // 4th attempt is blocked by the rate limiter itself (429) before it ever
    // reaches the credential check, so it can't fire a second notification.
    $this->post('/login', ['email' => $target->email, 'password' => 'wrong-password'])
        ->assertStatus(429);

    Notification::assertSentToTimes(User::where('is_admin', true)->first(), AdminAlert::class, 1);
});

it('does not notify admins for a single wrong-password attempt', function () {
    Notification::fake();

    User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['email' => 'victim3@example.com', 'password' => 'correct-password']);

    $this->post('/login', ['email' => $target->email, 'password' => 'wrong-password']);

    Notification::assertNothingSent();
});
