<?php

use App\Models\User;
use App\Notifications\AdminAlert;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated requests to the notifications api', function () {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
});

it('lists only the authenticated user\'s own notifications, newest first', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    $user->notify(new AdminAlert('First'));
    $this->travel(1)->second();
    $user->notify(new AdminAlert('Second'));
    $other->notify(new AdminAlert('Not mine'));

    $response = $this->getJson('/api/v1/notifications')->assertOk();

    expect($response->json('data'))->toHaveCount(2)
        ->and($response->json('data.0.data.title'))->toBe('Second')
        ->and($response->json('meta.total'))->toBe(2)
        ->and($response->json('meta.unread_count'))->toBe(2);
});

it('filters notifications by read status', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $user->notify(new AdminAlert('Unread one'));
    $user->notify(new AdminAlert('Will be read'));
    $user->notifications()->where('data->title', 'Will be read')->first()->markAsRead();

    $this->getJson('/api/v1/notifications?status=unread')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.data.title', 'Unread one');

    $this->getJson('/api/v1/notifications?status=read')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.data.title', 'Will be read');
});

it('marks a single notification as read', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $user->notify(new AdminAlert('Hello'));
    $id = $user->notifications()->first()->id;

    $this->postJson("/api/v1/notifications/{$id}/read")
        ->assertOk()
        ->assertJsonPath('data.id', $id)
        ->assertJsonPath('data.read_at', fn ($value) => $value !== null);

    expect($user->fresh()->unreadNotifications)->toHaveCount(0);
});

it('does not let a user mark another user\'s notification as read', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    $other->notify(new AdminAlert('Not yours'));
    $id = $other->notifications()->first()->id;

    $this->postJson("/api/v1/notifications/{$id}/read")->assertNotFound();

    expect($other->fresh()->unreadNotifications)->toHaveCount(1);
});

it('marks every notification as read at once', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $user->notify(new AdminAlert('One'));
    $user->notify(new AdminAlert('Two'));

    $this->postJson('/api/v1/notifications/read-all')->assertOk();

    expect($user->fresh()->unreadNotifications)->toHaveCount(0);
});

it('deletes a notification', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $user->notify(new AdminAlert('Delete me'));
    $id = $user->notifications()->first()->id;

    $this->deleteJson("/api/v1/notifications/{$id}")->assertNoContent();

    expect($user->fresh()->notifications)->toHaveCount(0);
});

it('does not let a user delete another user\'s notification', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    $other->notify(new AdminAlert('Not yours'));
    $id = $other->notifications()->first()->id;

    $this->deleteJson("/api/v1/notifications/{$id}")->assertNoContent();

    expect($other->fresh()->notifications)->toHaveCount(1);
});
