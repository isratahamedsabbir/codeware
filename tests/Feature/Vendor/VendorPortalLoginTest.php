<?php

use App\Livewire\Vendor\Auth\Login;
use App\Models\ProductVendor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('serves its own login page on the vendor host instead of the shared admin one', function () {
    $this->get('http://'.config('app.vendor_host').'/login')
        ->assertOk()
        ->assertSeeLivewire(Login::class);
});

it('logs a valid vendor in and lands them on the vendor dashboard', function () {
    $user = User::factory()->create(['is_admin' => false, 'password' => 'correct-password']);
    $user->assignRole('vendor');
    ProductVendor::factory()->create()->users()->attach($user);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertRedirect(route('vendor.dashboard'));

    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});

it('rejects a correct password for a user who is not a valid vendor, and does not log them in', function () {
    $admin = User::factory()->create(['is_admin' => true, 'password' => 'correct-password']);

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('rejects a wrong password', function () {
    $user = User::factory()->create(['is_admin' => false, 'password' => 'correct-password']);
    $user->assignRole('vendor');
    ProductVendor::factory()->create()->users()->attach($user);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('sends an already-authenticated valid vendor straight to the dashboard on mount', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->assignRole('vendor');
    ProductVendor::factory()->create()->users()->attach($user);

    Livewire::actingAs($user)
        ->test(Login::class)
        ->assertRedirect(route('vendor.dashboard'));
});

it('logs out a stale non-vendor session on mount instead of leaving it stuck on the login form', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)->test(Login::class);

    expect(auth()->check())->toBeFalse();
});

it('logs a vendor out via its own logout route back to the vendor login', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->assignRole('vendor');
    ProductVendor::factory()->create()->users()->attach($user);

    $this->actingAs($user)
        ->post(route('vendor.logout'))
        ->assertRedirect(route('vendor.login'));

    expect(auth()->check())->toBeFalse();
});
