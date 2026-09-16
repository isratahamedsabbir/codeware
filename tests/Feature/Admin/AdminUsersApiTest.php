<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
});

it('rejects unauthenticated requests to admin users api', function () {
    $this->getJson('/api/v1/admin/users')->assertUnauthorized();
});

it('rejects a plain customer account from the admin users api', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/admin/users')->assertForbidden();
});

it('rejects a staff-role user from the admin users api, unlike the general admin endpoints', function () {
    $staff = User::factory()->create();
    $staff->assignRole(Role::findOrCreate('staff', 'web'));
    Sanctum::actingAs($staff);

    // Staff passes the general admin gate (can reach e.g. products) ...
    $this->getJson('/api/v1/admin/products')->assertOk();

    // ... but user management is stricter (access-admin-system), same as the
    // Livewire admin panel — staff must not be able to manage other accounts.
    $this->getJson('/api/v1/admin/users')->assertForbidden();
    $this->postJson('/api/v1/admin/users', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertForbidden();
});

it('lists users with pagination and role info', function () {
    Sanctum::actingAs($this->admin);
    User::factory()->count(3)->create();

    $this->getJson('/api/v1/admin/users?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'email', 'roles']], 'meta'])
        ->assertJsonPath('meta.total', 4)
        ->assertJsonPath('meta.per_page', 2);
});

it('creates a user with roles', function () {
    Sanctum::actingAs($this->admin);
    Role::findOrCreate('staff', 'web');

    $this->postJson('/api/v1/admin/users', [
        'name' => 'New Staffer',
        'email' => 'staffer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['staff'],
    ])->assertCreated()
        ->assertJsonPath('data.email', 'staffer@example.com')
        ->assertJsonPath('data.roles.0', 'staff');

    $user = User::where('email', 'staffer@example.com')->sole();
    expect(Hash::check('password', $user->password))->toBeTrue();
});

it('rejects creating a user with a role that does not exist', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/users', [
        'name' => 'Bad Role',
        'email' => 'badrole@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['not-a-real-role'],
    ])->assertUnprocessable()->assertJsonValidationErrors(['roles.0']);
});

it('updates a user\'s name, email, and roles', function () {
    Sanctum::actingAs($this->admin);
    Role::findOrCreate('staff', 'web');
    $user = User::factory()->create();

    $this->putJson("/api/v1/admin/users/{$user->id}", [
        'name' => 'Renamed',
        'email' => 'renamed@example.com',
        'roles' => ['staff'],
    ])->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.roles.0', 'staff');

    $fresh = $user->fresh();
    expect($fresh->email)->toBe('renamed@example.com')
        ->and($fresh->hasRole('staff'))->toBeTrue();
});

it('does not touch the password when updating other user fields', function () {
    Sanctum::actingAs($this->admin);
    $user = User::factory()->create(['password' => 'original-password']);
    $originalHash = $user->password;

    $this->putJson("/api/v1/admin/users/{$user->id}", ['name' => 'Renamed Only'])
        ->assertOk();

    expect($user->fresh()->password)->toBe($originalHash);
});

it('changes a user\'s password via the dedicated endpoint', function () {
    Sanctum::actingAs($this->admin);
    $user = User::factory()->create(['password' => 'old-password']);

    $this->putJson("/api/v1/admin/users/{$user->id}/password", [
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertOk();

    expect(Hash::check('brand-new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects a password change without confirmation', function () {
    Sanctum::actingAs($this->admin);
    $user = User::factory()->create();

    $this->putJson("/api/v1/admin/users/{$user->id}/password", ['password' => 'brand-new-password'])
        ->assertUnprocessable()->assertJsonValidationErrors(['password']);
});

it('deletes a user', function () {
    Sanctum::actingAs($this->admin);
    $user = User::factory()->create();

    $this->deleteJson("/api/v1/admin/users/{$user->id}")->assertNoContent();

    expect(User::find($user->id))->toBeNull();
});

it('prevents an admin from deleting their own account', function () {
    Sanctum::actingAs($this->admin);

    $this->deleteJson("/api/v1/admin/users/{$this->admin->id}")
        ->assertUnprocessable();

    expect(User::find($this->admin->id))->not->toBeNull();
});
