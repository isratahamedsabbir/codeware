<?php

use App\Models\Subscriber;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lists subscribers with pagination', function () {
    Subscriber::factory()->count(5)->create();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/subscribers?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.per_page', 2);
});

it('filters subscribers by status', function () {
    Subscriber::factory()->create(['status' => 'subscribed']);
    Subscriber::factory()->unsubscribed()->create();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/subscribers?status=subscribed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'subscribed');
});

it('searches subscribers by email', function () {
    Subscriber::factory()->create(['email' => 'jane@example.com']);
    Subscriber::factory()->create(['email' => 'john@example.com']);

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/subscribers?search=jane')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'jane@example.com');
});

it('shows a single subscriber', function () {
    $subscriber = Subscriber::factory()->create();

    $this->actingAs($this->admin)
        ->getJson("/api/v1/admin/subscribers/{$subscriber->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'email', 'status', 'created_at']])
        ->assertJsonPath('data.id', $subscriber->id);
});

it('deletes a subscriber', function () {
    $subscriber = Subscriber::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/admin/subscribers/{$subscriber->id}")
        ->assertNoContent();

    expect(Subscriber::find($subscriber->id))->toBeNull();
});

it('requires admin authentication', function () {
    $this->getJson('/api/v1/admin/subscribers')->assertUnauthorized();
});
