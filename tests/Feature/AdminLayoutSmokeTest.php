<?php

use App\Models\User;

test('admin layout renders header and footer', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee('data-flux-header', false);
    $response->assertSee('data-flux-breadcrumbs', false);
    $response->assertSee('All rights reserved');
    $response->assertSee('v1.0.0');
    $response->assertSee('My Profile');
});

test('admin subpage renders breadcrumb section', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $response = $this->get(route('admin.users'));
    $response->assertOk();
    $response->assertSee('Access Control');
    $response->assertSee('Users');
});
