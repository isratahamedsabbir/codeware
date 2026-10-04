<?php

use App\Models\Setting;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());

    // The registration page is a theme template (themes/{slug}/auth/register),
    // so the suite runs on the one bundled theme that ships it.
    Setting::set('site_theme', 'ecommerce');
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        // The account area of the theme that has one — see
        // FortifyServiceProvider::accountPath().
        ->assertRedirect(route('account.dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('a newly registered user is assigned the customer role', function () {
    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'test@example.com')->sole()->hasRole('customer'))->toBeTrue();
});

test('registered email is stored lowercase regardless of input case', function () {
    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'Test@Example.COM',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    expect(User::where('name', 'John Doe')->sole()->email)->toBe('test@example.com');
});
