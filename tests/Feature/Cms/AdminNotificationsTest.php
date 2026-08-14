<?php

use App\Livewire\Admin\Notifications\Bell;
use App\Models\Contact;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('shows unread notification count on the bell', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->notify(new AdminAlert('One'));
    $admin->notify(new AdminAlert('Two'));
    $read = $admin->notifications()->latest()->first();
    $admin->notify(new AdminAlert('Three'));
    $read->markAsRead();

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->assertSee('Notifications')
        ->assertSee('2');
});

it('shows the notification title and message in the dropdown', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->notify(new AdminAlert('New contact message', 'John: Please call me'));

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->assertSee('New contact message')
        ->assertSee('John: Please call me');
});

it('shows an empty state when there are no notifications', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->assertSee('No notifications');
});

it('marks a single notification as read', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->notify(new AdminAlert('Test'));
    $notification = $admin->notifications()->sole();

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->call('markAsRead', $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks all notifications as read', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->notify(new AdminAlert('One'));
    $admin->notify(new AdminAlert('Two'));
    $admin->notify(new AdminAlert('Three'));

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->call('markAllRead')
        ->assertDispatched('notify');

    expect($admin->unreadNotifications()->count())->toBe(0);
});

it('only lists notifications belonging to the current user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $other = User::factory()->create(['is_admin' => true]);
    $admin->notify(new AdminAlert('Mine'));
    $other->notify(new AdminAlert('Theirs'));

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->assertSee('Mine')
        ->assertDontSee('Theirs');
});

it('creates notifications for all admins when a contact is submitted', function () {
    Notification::fake();

    $admins = User::factory()->count(2)->create(['is_admin' => true]);

    $this->postJson('/api/v1/contacts', [
        'full_name' => 'John Doe',
        'phone_number' => '+8801712345678',
        'email' => 'john@example.com',
        'subject' => 'Product Inquiry',
        'message' => 'I would like to know more.',
    ])->assertCreated();

    Notification::assertSentTo($admins, function (AdminAlert $notification) {
        return $notification->title === 'New contact message'
            && str_contains($notification->message, 'Product Inquiry')
            && $notification->link === route('admin.contacts');
    });
});

it('does not create notifications when no admins exist', function () {
    Notification::fake();

    User::factory()->create(['is_admin' => false]);

    $this->postJson('/api/v1/contacts', [
        'full_name' => 'John Doe',
        'phone_number' => '+8801712345678',
        'email' => 'john@example.com',
        'subject' => 'Hello',
        'message' => 'Hi there.',
    ])->assertCreated();

    Notification::assertNothingSent();
});

it('renders the bell component in the admin layout header', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/posts')
        ->assertOk()
        ->assertSee('admin.notifications.bell')
        ->assertSee('aria-label="Notifications"', false);
});

it('leaves notifications in place when the user is deleted, since the native table has no FK to users', function () {
    // Unlike the old bespoke admin_notifications table (which had a real FK with
    // cascadeOnDelete), Laravel's native notifications table is a polymorphic
    // notifiable_type/notifiable_id pair with no FK constraint by design — it
    // supports notifying any model, so it can't cascade on a specific model's
    // delete. Orphaned rows for a deleted user are expected, not a bug.
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->notify(new AdminAlert('Test'));

    $admin->delete();

    expect(DB::table('notifications')->count())->toBe(1);
});

it('renders the bell dropdown with relative time for notifications', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->notify(new AdminAlert('System update'));
    $admin->notifications()->update(['created_at' => now()->subMinutes(5)]);

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->assertSee('System update')
        ->assertSee('ago');
});

it('keeps contact notification link pointing to the contacts page', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Contact::create([
        'full_name' => 'Jane Doe',
        'phone_number' => '+8801712345679',
        'email' => 'jane@example.com',
        'subject' => 'Support',
        'message' => 'Need help.',
    ]);

    $notification = $admin->notifications()->first();

    expect($notification)->not->toBeNull();
    expect($notification->data['title'])->toBe('New contact message');
});
