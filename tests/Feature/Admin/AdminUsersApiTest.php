<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['is_admin' => true]);
});

it('rejects unauthenticated requests to admin users api', function () {
    $this->getJson('/api/v1/admin/users')->assertUnauthorized();
});

it('rejects a plain customer account from the admin users api', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->getJson('/api/v1/admin/users')->assertForbidden();
});

it('rejects a staff-role user from the admin users api, unlike the general admin endpoints', function () {
    $staff = User::factory()->create(['is_admin' => false]);
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

it('allows an admin-role (non-super-admin) user into the admin users api', function () {
    $admin = User::factory()->create(['is_admin' => false]);
    $admin->assignRole(Role::findOrCreate('admin', 'web'));
    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/users')->assertOk();
});

it('lists users with pagination and role info', function () {
    Sanctum::actingAs($this->superAdmin);
    User::factory()->count(3)->create();

    $this->getJson('/api/v1/admin/users?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'email', 'is_admin', 'roles']], 'meta'])
        ->assertJsonPath('meta.total', 4)
        ->assertJsonPath('meta.per_page', 2);
});

it('creates a user with roles', function () {
    Sanctum::actingAs($this->superAdmin);
    Role::findOrCreate('staff', 'web');

    $this->postJson('/api/v1/admin/users', [
        'name' => 'New Staffer',
        'email' => 'staffer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['staff'],
    ])->assertCreated()
        ->assertJsonPath('data.email', 'staffer@example.com')
        ->assertJsonPath('data.roles.0', 'staff')
        ->assertJsonPath('data.is_admin', false);

    $user = User::where('email', 'staffer@example.com')->sole();
    expect(Hash::check('password', $user->password))->toBeTrue();
});

it('rejects creating a user with a role that does not exist', function () {
    Sanctum::actingAs($this->superAdmin);

    $this->postJson('/api/v1/admin/users', [
        'name' => 'Bad Role',
        'email' => 'badrole@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['not-a-real-role'],
    ])->assertUnprocessable()->assertJsonValidationErrors(['roles.0']);
});

it('updates a user\'s name, email, is_admin, and roles', function () {
    Sanctum::actingAs($this->superAdmin);
    Role::findOrCreate('staff', 'web');
    $user = User::factory()->create(['is_admin' => false]);

    $this->putJson("/api/v1/admin/users/{$user->id}", [
        'name' => 'Renamed',
        'email' => 'renamed@example.com',
        'is_admin' => true,
        'roles' => ['staff'],
    ])->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.is_admin', true)
        ->assertJsonPath('data.roles.0', 'staff');

    $fresh = $user->fresh();
    expect($fresh->email)->toBe('renamed@example.com')
        ->and((bool) $fresh->is_admin)->toBeTrue();
});

it('does not touch the password when updating other user fields', function () {
    Sanctum::actingAs($this->superAdmin);
    $user = User::factory()->create(['password' => 'original-password']);
    $originalHash = $user->password;

    $this->putJson("/api/v1/admin/users/{$user->id}", ['name' => 'Renamed Only'])
        ->assertOk();

    expect($user->fresh()->password)->toBe($originalHash);
});

it('changes a user\'s password via the dedicated endpoint', function () {
    Sanctum::actingAs($this->superAdmin);
    $user = User::factory()->create(['password' => 'old-password']);

    $this->putJson("/api/v1/admin/users/{$user->id}/password", [
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertOk();

    expect(Hash::check('brand-new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects a password change without confirmation', function () {
    Sanctum::actingAs($this->superAdmin);
    $user = User::factory()->create();

    $this->putJson("/api/v1/admin/users/{$user->id}/password", ['password' => 'brand-new-password'])
        ->assertUnprocessable()->assertJsonValidationErrors(['password']);
});

it('deletes a user', function () {
    Sanctum::actingAs($this->superAdmin);
    $user = User::factory()->create();

    $this->deleteJson("/api/v1/admin/users/{$user->id}")->assertNoContent();

    expect(User::find($user->id))->toBeNull();
});

it('prevents an admin from deleting their own account', function () {
    Sanctum::actingAs($this->superAdmin);

    $this->deleteJson("/api/v1/admin/users/{$this->superAdmin->id}")
        ->assertUnprocessable();

    expect(User::find($this->superAdmin->id))->not->toBeNull();
});
