<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
});

test('two factor challenge redirects to login when not authenticated', function () {
    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

test('two factor challenge can be rendered', function () {
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    // Having a factor is not on its own a reason to be challenged — the account's
    // role has to have asked for one (Admin → Roles), or a customer who enrolled
    // a factor years ago would be challenged on every sign-in forever.
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->withTwoFactor()->create();

    $user->assignRole('customer');
    activateRoles('customer');

    Role::where('name', 'customer')->update(['mfa_enabled' => true]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));
});

test('an account with a factor is not challenged when its role did not ask for one', function () {
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->withTwoFactor()->create();

    $user->assignRole('customer');
    activateRoles('customer');

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));
});
