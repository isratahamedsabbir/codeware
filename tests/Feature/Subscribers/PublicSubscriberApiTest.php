<?php

use App\Models\Subscriber;

it('subscribes a new email', function () {
    $this->postJson('/api/v1/subscribers', ['email' => 'john@example.com'])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'email', 'status']])
        ->assertJsonPath('message', 'Thanks for subscribing!');

    $this->assertDatabaseHas('subscribers', [
        'email' => 'john@example.com',
        'status' => 'subscribed',
    ]);
});

it('returns a friendly message when already subscribed', function () {
    Subscriber::factory()->create(['email' => 'john@example.com', 'status' => 'subscribed']);

    $this->postJson('/api/v1/subscribers', ['email' => 'john@example.com'])
        ->assertOk()
        ->assertJsonPath('message', 'You are already subscribed.');

    expect(Subscriber::where('email', 'john@example.com')->count())->toBe(1);
});

it('resubscribes a previously unsubscribed email', function () {
    Subscriber::factory()->unsubscribed()->create(['email' => 'john@example.com']);

    $this->postJson('/api/v1/subscribers', ['email' => 'john@example.com'])
        ->assertCreated()
        ->assertJsonPath('message', 'Thanks for subscribing!');

    expect(Subscriber::where('email', 'john@example.com')->first()->status)->toBe('subscribed');
});

it('validates required fields', function () {
    $this->postJson('/api/v1/subscribers', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('validates email format', function () {
    $this->postJson('/api/v1/subscribers', ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
