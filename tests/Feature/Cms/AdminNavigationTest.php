<?php

use App\Models\User;

test('non-admin authenticated users are redirected into the admin panel and denied access', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertRedirect('/admin');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('admin users are redirected from dashboard straight into the admin panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get(route('dashboard'));
    $response->assertRedirect('/admin');

    $this->actingAs($admin)->get('/admin')->assertOk();
});

test('admins see the back to site link in the admin layout', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get('/admin/posts');

    $response->assertOk();
    $response->assertSee('Open frontend');
});
