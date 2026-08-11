<?php

use App\Models\User;

test('non-admin users do not see the admin panel link on the dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);
    
    $response = $this->actingAs($user)->get(route('dashboard'));
    
    $response->assertOk();
    $response->assertDontSee('Admin Panel');
});

test('admin users see the admin panel link on the dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    $response = $this->actingAs($admin)->get(route('dashboard'));
    
    $response->assertOk();
    $response->assertSee('Admin Panel');
});

test('admins see the back to site link in the admin layout', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    
    $response = $this->actingAs($admin)->get('/admin/posts');
    
    $response->assertOk();
    $response->assertSee('Back to Site');
});
