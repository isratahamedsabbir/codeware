<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated requests to the profile api', function () {
    $this->getJson('/api/v1/profile')->assertUnauthorized();
});

it('returns the authenticated customer\'s own profile', function () {
    $user = User::factory()->create(['name' => 'Original Name']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', 'Original Name')
        ->assertJsonPath('data.email', $user->email);
});

it('updates the customer\'s name without affecting email verification', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile', [
        'name' => 'New Name',
        'email' => $user->email,
    ])->assertOk()->assertJsonPath('data.name', 'New Name');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('resets verification and resends the email when the customer changes their email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'old@example.com']);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile', [
        'name' => $user->name,
        'email' => 'new@example.com',
    ])->assertOk()->assertJsonPath('data.email', 'new@example.com');

    $fresh = $user->fresh();
    expect($fresh->email)->toBe('new@example.com')
        ->and($fresh->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($fresh, VerifyEmail::class);
});

it('rejects a profile update with an email already taken by another user', function () {
    $other = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile', [
        'name' => $user->name,
        'email' => 'taken@example.com',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});
