<?php

use App\Models\Setting;
use App\Models\User;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('login screen shows the uploaded site icon', function () {
    Setting::set('site_icon', '/storage/site-icon.png');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('src="/storage/site-icon.png"', false);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('a blocked user cannot authenticate even with the correct password', function () {
    $user = User::factory()->create(['is_blocked' => true]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrorsIn('email');
    $response->assertSessionHas('error', 'Your account has been blocked.');

    $this->assertGuest();
});

test('users with a deactivated role cannot authenticate even with the correct password', function () {
    $role = Role::findOrCreate('staff', 'web');
    $role->update(['status' => 'inactive']);

    $user = User::factory()->create(['is_admin' => false]);
    $user->assignRole($role);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrorsIn('email');
    $response->assertSessionHas('error', 'Your account access has been disabled.');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
