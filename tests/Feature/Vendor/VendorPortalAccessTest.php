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
    $user = User::factory()->create();
    $user->assignRole('vendor');

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('vendor.products'))->assertForbidden();
    $this->actingAs($user)->get(route('vendor.orders'))->assertForbidden();
});

test('a user assigned to a vendor but without the vendor role is forbidden from the vendor portal', function () {
    $user = User::factory()->create();
    $vendor = ProductVendor::factory()->create();
    $vendor->users()->attach($user);

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertForbidden();
});

test('a user with the vendor role and an assigned vendor can access the vendor portal', function () {
    $user = User::factory()->create();
    $user->assignRole('vendor');
    $vendor = ProductVendor::factory()->create();
    $vendor->users()->attach($user);

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertOk();
    $this->actingAs($user)->get(route('vendor.products'))->assertOk();
    $this->actingAs($user)->get(route('vendor.orders'))->assertOk();
});

test('an admin user is not automatically granted vendor portal access', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('vendor.dashboard'))->assertForbidden();
});

test('the dashboard route always sends to the admin panel, never bounces to the vendor host', function () {
    // The vendor portal has its own separate login/session on its own host
    // (host-only cookies — see .env's SESSION_DOMAIN), so a session here can
    // never carry over there. The admin panel likewise lives on its own host,
    // so /dashboard redirects straight to admin.codeware.test — a vendor-only
    // account lands there and gets the normal 403 from AdminMiddleware, which
    // is a clearer outcome than bouncing to the vendor login as a guest.
    $admin = User::factory()->admin()->create();
    $regular = User::factory()->create();
    $vendorOnly = User::factory()->create();
    $vendorOnly->assignRole('vendor');
    ProductVendor::factory()->create()->users()->attach($vendorOnly);

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(config('app.admin_url'));
    $this->actingAs($regular)->get(route('dashboard'))->assertRedirect(config('app.admin_url'));

    $this->actingAs($vendorOnly)->get(route('dashboard'))->assertRedirect(config('app.admin_url'));
    $this->actingAs($vendorOnly)->get(config('app.admin_url'))->assertForbidden();
});
