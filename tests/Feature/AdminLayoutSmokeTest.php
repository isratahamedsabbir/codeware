<?php

use App\Models\User;
use Database\Seeders\PaymentGatewaySeeder;
use Spatie\Permission\Models\Role;

test('admin layout renders header and footer', function () {
    Role::findOrCreate('admin', 'web');
    $user = User::factory()->admin()->create();
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
    Role::findOrCreate('admin', 'web');
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.users'));
    $response->assertOk();
    $response->assertSee('Access Control');
    $response->assertSee('Users');
});

test('payment gateways page renders gateway credentials', function () {
    Role::findOrCreate('admin', 'web');
    $user = User::factory()->admin()->create();
    $this->actingAs($user);
    $this->seed(PaymentGatewaySeeder::class);

    $response = $this->get(route('admin.payment-gateways'));
    $response->assertOk();
    $response->assertSee('PayPal');
    $response->assertSee('Stripe');
    $response->assertSee('bKash');
    $response->assertSee('SSLCommerz');
    $response->assertSee('Apple Pay');
});
