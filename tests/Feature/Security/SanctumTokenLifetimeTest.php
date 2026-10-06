<?php

use App\Models\User;
use App\Support\ApiToken;
use Spatie\Permission\Models\Role;

it('expires tokens globally and sooner for admins', function () {
    expect(config('sanctum.expiration'))->toBe(43200);

    Role::findOrCreate('admin');
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $customer = User::factory()->create();

    $adminToken = $admin->tokens()->find(explode('|', ApiToken::issue($admin))[0]);
    $customerToken = $customer->tokens()->find(explode('|', ApiToken::issue($customer))[0]);

    expect($adminToken->expires_at->between(now()->addHours(7), now()->addHours(9)))->toBeTrue()
        ->and($customerToken->expires_at)->toBeNull();
});

it('rejects an expired token', function () {
    $user = User::factory()->create();
    $plain = $user->createToken('t', ['*'], now()->subMinute())->plainTextToken;

    $this->withToken($plain)->getJson('/api/v1/profile')->assertUnauthorized();
});

it('revokes all tokens when the password changes', function () {
    $user = User::factory()->create();
    $user->createToken('a');
    $user->createToken('b');

    $user->update(['password' => 'a-new-Passw0rd!x']);

    expect($user->tokens()->count())->toBe(0);
});

it('keeps tokens when other fields change', function () {
    $user = User::factory()->create();
    $user->createToken('a');

    $user->update(['name' => 'Renamed']);

    expect($user->tokens()->count())->toBe(1);
});
