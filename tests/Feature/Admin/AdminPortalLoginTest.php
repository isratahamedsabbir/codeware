<?php

use App\Livewire\Admin\Auth\Login;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('serves its own login page on the admin host instead of the shared main-site one', function () {
    $this->get(config('app.admin_url').'/login')
        ->assertOk()
        ->assertSeeLivewire(Login::class);
});

it('logs a valid admin in and lands them on the admin dashboard', function () {
    $admin = User::factory()->admin()->create(['password' => 'correct-password']);

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($admin->id);
});

it('logs a staff member in too, since staff passes the access-admin gate', function () {
    $staff = User::factory()->staff()->create(['password' => 'correct-password']);

    Livewire::test(Login::class)
        ->set('email', $staff->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($staff->id);
});

it('rejects a correct password for a user who is neither admin nor staff, and does not log them in', function () {
    $regular = User::factory()->create(['password' => 'correct-password']);

    Livewire::test(Login::class)
        ->set('email', $regular->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('rejects a staff member whose role has been deactivated, with a message distinct from "not an admin"', function () {
    $user = User::factory()->staff()->create(['password' => 'correct-password']);

    $user->roles()->first()->update(['status' => 'inactive']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertHasErrors('email')
        ->assertDispatched('notify', message: 'Your account access has been disabled.', type: 'error');

    expect(auth()->check())->toBeFalse();
});

it('rejects a blocked admin, even with the correct password', function () {
    $admin = User::factory()->admin()->create(['password' => 'correct-password', 'is_blocked' => true]);

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertHasErrors('email')
        ->assertDispatched('notify', message: 'Your account has been blocked.', type: 'error');

    expect(auth()->check())->toBeFalse();
});

it('rejects a wrong password', function () {
    $admin = User::factory()->admin()->create(['password' => 'correct-password']);

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('sends an admin with two-factor enabled to the challenge on the admin host, without logging them in yet', function () {
    $admin = User::factory()->admin()->withTwoFactor()->create(['password' => 'correct-password']);

    Livewire::test(Login::class)
        ->set('email', $admin->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertRedirect(config('app.admin_url').'/two-factor-challenge');

    expect(auth()->check())->toBeFalse();
    expect(session('login.id'))->toBe($admin->id);
});

it('sends an already-authenticated valid admin straight to the dashboard on mount', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Login::class)
        ->assertRedirect(route('admin.dashboard'));
});

it('logs out a stale non-admin session on mount instead of leaving it stuck on the login form', function () {
    $regular = User::factory()->create();

    Livewire::actingAs($regular)->test(Login::class);

    expect(auth()->check())->toBeFalse();
});

it('logs an admin out via its own logout route back to the admin login', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    expect(auth()->check())->toBeFalse();
});
