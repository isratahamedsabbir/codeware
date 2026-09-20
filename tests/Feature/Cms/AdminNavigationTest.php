<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('non-admin authenticated users are redirected into the admin panel and denied access', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertRedirect(config('app.admin_url'));

    $this->actingAs($user)->get(config('app.admin_url'))->assertForbidden();
});

test('admin users are redirected from dashboard straight into the admin panel', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));
    $response->assertRedirect(config('app.admin_url'));

    $this->actingAs($admin)->get(config('app.admin_url'))->assertOk();
});

test('admins see the back to site link in the admin layout', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(config('app.admin_url').'/posts');

    $response->assertOk();
    $response->assertSee('Open frontend');
});
