<?php

use App\Livewire\Admin\PaymentGateways\Index as PaymentGatewaysIndex;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Support\PaymentMethods;
use Database\Seeders\PaymentGatewaySeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('seeder creates the default payment gateway rows, disabled by default', function () {
    $this->artisan('db:seed', ['--class' => PaymentGatewaySeeder::class]);

    foreach (['paypal', 'stripe', 'bkash', 'sslcommerz', 'applepay'] as $code) {
        $gateway = PaymentGateway::where('code', $code)->first();

        expect($gateway)->not->toBeNull()
            ->and($gateway->is_enabled)->toBeFalse();
    }
});

it('renders the payment gateways screen', function () {
    $this->artisan('db:seed', ['--class' => PaymentGatewaySeeder::class]);

    Livewire::test(PaymentGatewaysIndex::class)
        ->assertStatus(200)
        ->assertSee('PayPal')
        ->assertSee('Stripe')
        ->assertSee('bKash')
        ->assertSee('SSLCommerz')
        ->assertSee('Apple Pay');
});

it('is reachable at its own admin route', function () {
    $this->artisan('db:seed', ['--class' => PaymentGatewaySeeder::class]);

    $this->get(route('admin.payment-gateways'))->assertOk();
});

it('saves gateway credentials and enabled state through the form', function () {
    $this->artisan('db:seed', ['--class' => PaymentGatewaySeeder::class]);

    Livewire::test(PaymentGatewaysIndex::class)
        ->set('gateways.stripe.is_enabled', true)
        ->set('gateways.stripe.mode', 'live')
        ->set('gateways.stripe.credentials.publishable_key', 'pk_live_123')
        ->set('gateways.stripe.credentials.secret_key', 'sk_live_123')
        ->call('save')
        ->assertHasNoErrors();

    $stripe = PaymentGateway::where('code', 'stripe')->firstOrFail();

    expect($stripe->is_enabled)->toBeTrue()
        ->and($stripe->mode)->toBe('live')
        ->and($stripe->credential('publishable_key'))->toBe('pk_live_123')
        ->and($stripe->credential('secret_key'))->toBe('sk_live_123');
});

it('only lists enabled gateways as available payment methods, alongside cash on delivery', function () {
    $this->artisan('db:seed', ['--class' => PaymentGatewaySeeder::class]);

    expect(PaymentMethods::available())->toBe(['cod' => 'Cash on Delivery']);

    PaymentGateway::where('code', 'stripe')->update(['is_enabled' => true]);

    expect(PaymentMethods::available())->toBe(['cod' => 'Cash on Delivery', 'stripe' => 'Stripe'])
        ->and(PaymentMethods::isAvailable('stripe'))->toBeTrue()
        ->and(PaymentMethods::isAvailable('paypal'))->toBeFalse();
});

it('blocks staff from the payment gateways screen', function () {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('admin.payment-gateways'))->assertForbidden();
});
