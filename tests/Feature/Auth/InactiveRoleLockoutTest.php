<?php

use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
 * Deactivating a role (Admin → Roles) must lock every holder out everywhere:
 * no new login on any door (web, vendor, delivery, API), and anyone already
 * signed in is logged out on their next request — whatever kept them signed
 * in (a session, a "remember me" cookie, an API token).
 */
beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::findOrCreate('admin', 'web');
    $this->role = Role::findOrCreate('customer', 'web');

    $this->user = User::factory()->create(['password' => 'correct-password']);
    $this->user->assignRole($this->role);

    Route::middleware('api')->get('/api/__whoami', fn () => ['id' => auth('sanctum')->id()]);
});

it('logs an already signed-in user out on their next web request', function () {
    $this->actingAs($this->user)->get('/')->assertOk();
    $this->assertAuthenticatedAs($this->user);

    $this->role->update(['status' => 'inactive']);

    $this->get('/')->assertForbidden();
    $this->assertGuest();
});

it('leaves a user with only active roles signed in', function () {
    $this->actingAs($this->user)->get('/')->assertOk();
    $this->get('/')->assertOk();

    $this->assertAuthenticatedAs($this->user);
});

it('rejects an API bearer token and revokes it', function () {
    $token = $this->user->createToken('customer-api')->plainTextToken;

    $this->withToken($token)->getJson('/api/__whoami')
        ->assertOk()
        ->assertJson(['id' => $this->user->id]);

    $this->role->update(['status' => 'inactive']);
    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/__whoami')->assertForbidden();

    expect($this->user->tokens()->count())->toBe(0);
});

it('refuses an API login with the correct password', function () {
    $this->role->update(['status' => 'inactive']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $this->user->email,
        'password' => 'correct-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Your account access has been disabled.']);

    expect($this->user->tokens()->count())->toBe(0);
});

it('refuses an API login for a blocked account', function () {
    $this->user->forceFill(['is_blocked' => true])->save();

    $this->postJson('/api/v1/auth/login', [
        'email' => $this->user->email,
        'password' => 'correct-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Your account has been blocked.']);
});

it('revokes API and remember-me tokens of every holder when the role is deactivated', function () {
    $this->user->createToken('customer-api');
    $this->user->forceFill(['remember_token' => 'remember-me'])->save();

    $bystander = User::factory()->create(['remember_token' => 'keep-me']);
    $bystander->createToken('customer-api');

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(RolesIndex::class)->call('toggleStatus', $this->role->id);

    expect($this->user->tokens()->count())->toBe(0)
        ->and($this->user->fresh()->remember_token)->toBeNull()
        ->and($bystander->tokens()->count())->toBe(1)
        ->and($bystander->fresh()->remember_token)->toBe('keep-me');
});

it('does not revoke anything when a role is reactivated', function () {
    $this->role->update(['status' => 'inactive']);
    $this->user->createToken('customer-api');

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(RolesIndex::class)->call('toggleStatus', $this->role->id);

    expect($this->role->fresh()->status)->toBe('active')
        ->and($this->user->tokens()->count())->toBe(1)
        ->and(DB::table('personal_access_tokens')->count())->toBe(1);
});
