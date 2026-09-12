<?php

use App\Models\ProductVendor;
use App\Models\User;

test('guests are redirected to login from the vendor portal', function () {
    $this->get(route('vendor.dashboard'))->assertRedirect('/login');
});

test('a user with no vendors assigned is forbidden from the vendor portal', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('vendor.products'))->assertForbidden();
    $this->actingAs($user)->get(route('vendor.orders'))->assertForbidden();
});

test('a user assigned to a vendor can access the vendor portal', function () {
    $user = User::factory()->create(['is_admin' => false]);
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

test('the dashboard route sends a vendor-only user to the vendor portal', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $vendor = ProductVendor::factory()->create();
    $vendor->users()->attach($user);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('vendor.dashboard'));
});

test('the dashboard route still sends everyone else to the admin panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $regular = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect('/admin');
    $this->actingAs($regular)->get(route('dashboard'))->assertRedirect('/admin');
});
