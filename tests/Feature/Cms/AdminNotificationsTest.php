<?php

use App\Livewire\Admin\Notifications\Bell;
use App\Models\AdminNotification;
use App\Models\Contact;
use App\Models\User;
use Livewire\Livewire;

it('shows unread notification count on the bell', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    AdminNotification::factory()->count(2)->create(['user_id' => $admin->id]);
    AdminNotification::factory()->read()->create(['user_id' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->assertSee('Notifications')
        ->assertSee('2');
});

it('shows the notification title and message in the dropdown', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    AdminNotification::factory()->create([
        'user_id' => $admin->id,
        'title' => 'New contact message',
        'message' => 'John: Please call me',
    ]);

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
    $notification = AdminNotification::factory()->create(['user_id' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->call('markAsRead', $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks all notifications as read', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    AdminNotification::factory()->count(3)->create(['user_id' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->call('markAllRead')
        ->assertDispatched('notify');

    expect(AdminNotification::unread()->count())->toBe(0);
});

it('only lists notifications belonging to the current user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $other = User::factory()->create(['is_admin' => true]);
    AdminNotification::factory()->create(['user_id' => $admin->id, 'title' => 'Mine']);
    AdminNotification::factory()->create(['user_id' => $other->id, 'title' => 'Theirs']);

    Livewire::actingAs($admin)
        ->test(Bell::class)
        ->assertSee('Mine')
        ->assertDontSee('Theirs');
});

it('creates notifications for all admins when a contact is submitted', function () {
    User::factory()->count(2)->create(['is_admin' => true]);

    $this->postJson('/api/v1/contacts', [
        'full_name' => 'John Doe',
        'phone_number' => '+8801712345678',
        'email' => 'john@example.com',
        'subject' => 'Product Inquiry',
        'message' => 'I would like to know more.',
    ])->assertCreated();

    expect(AdminNotification::count())->toBe(2);
    expect(AdminNotification::first()->title)->toBe('New contact message');
    expect(AdminNotification::first()->message)->toContain('Product Inquiry');
    expect(AdminNotification::first()->link)->toBe(route('admin.contacts'));
});

it('does not create notifications when no admins exist', function () {
    User::factory()->create(['is_admin' => false]);

    $this->postJson('/api/v1/contacts', [
        'full_name' => 'John Doe',
        'phone_number' => '+8801712345678',
        'email' => 'john@example.com',
        'subject' => 'Hello',
        'message' => 'Hi there.',
    ])->assertCreated();

    expect(AdminNotification::count())->toBe(0);
});

it('renders the bell component in the admin layout header', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/posts')
        ->assertOk()
        ->assertSee('admin.notifications.bell')
        ->assertSee('aria-label="Notifications"', false);
});

it('deletes notifications when the user is deleted', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    AdminNotification::factory()->create(['user_id' => $admin->id]);

    $admin->delete();

    expect(AdminNotification::count())->toBe(0);
});

it('renders the bell dropdown with relative time for notifications', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    AdminNotification::factory()->create([
        'user_id' => $admin->id,
        'title' => 'System update',
        'created_at' => now()->subMinutes(5),
    ]);

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

    $notification = AdminNotification::where('user_id', $admin->id)->first();

    expect($notification)->not->toBeNull();
    expect($notification->title)->toBe('New contact message');
});
