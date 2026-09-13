<?php

use App\Models\ProductVendor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('guests are redirected to login from the vendor portal', function () {
    $this->get(route('vendor.dashboard'))->assertRedirect('/login');
});

test('a user with no vendors assigned is forbidden from the vendor portal', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->assignRole('vendor');

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('vendor.products'))->assertForbidden();
    $this->actingAs($user)->get(route('vendor.orders'))->assertForbidden();
});

test('a user assigned to a vendor but without the vendor role is forbidden from the vendor portal', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $vendor = ProductVendor::factory()->create();
    $vendor->users()->attach($user);

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertForbidden();
});

test('a user with the vendor role and an assigned vendor can access the vendor portal', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->assignRole('vendor');
    $vendor = ProductVendor::factory()->create();
    $vendor->users()->attach($user);

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertOk();
    $this->actingAs($user)->get(route('vendor.products'))->assertOk();
    $this->actingAs($user)->get(route('vendor.orders'))->assertOk();
});

test('an admin user is not automatically granted vendor portal access', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('vendor.dashboard'))->assertForbidden();
});

test('the dashboard route always sends to the admin panel, never bounces to the vendor host', function () {
    // The vendor portal has its own separate login/session on its own host
    // (host-only cookies — see .env's SESSION_DOMAIN), so a session here can
    // never carry over there. Redirecting a vendor-only account to the
    // vendor host would just drop them logged-out on its login page with no
    // explanation — the normal 403 from AdminMiddleware below is clearer.
    $admin = User::factory()->create(['is_admin' => true]);
    $regular = User::factory()->create(['is_admin' => false]);
    $vendorOnly = User::factory()->create(['is_admin' => false]);
    $vendorOnly->assignRole('vendor');
    ProductVendor::factory()->create()->users()->attach($vendorOnly);

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect('/admin');
    $this->actingAs($regular)->get(route('dashboard'))->assertRedirect('/admin');

    $this->actingAs($vendorOnly)->get(route('dashboard'))->assertRedirect('/admin');
    $this->actingAs($vendorOnly)->get('/admin')->assertForbidden();
});
