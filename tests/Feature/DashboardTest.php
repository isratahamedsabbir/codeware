<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated admins are redirected from dashboard into the admin panel', function () {
    Role::findOrCreate('admin', 'web');
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(config('app.admin_url'));

    $this->get(config('app.admin_url'))->assertOk();
});
