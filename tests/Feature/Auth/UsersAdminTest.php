<?php

use App\Livewire\Admin\Users\Form as UsersForm;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);

    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->role = Role::findOrCreate('manager', 'web');
});

it('renders users index', function () {
    Livewire::test(UsersIndex::class)->assertStatus(200);
});

it('displays users in the table', function () {
    User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    Livewire::test(UsersIndex::class)->assertSee('Jane Doe');
});

it('can filter users by role', function () {
    User::factory()->create(['name' => 'Manager User', 'email' => 'manager@example.com'])->assignRole('manager');
    User::factory()->create(['name' => 'Plain User', 'email' => 'plain@example.com']);

    Livewire::test(UsersIndex::class)
        ->set('roleFilter', 'manager')
        ->assertSee('Manager User')
        ->assertDontSee('Plain User');
});

it('renders user form for creation', function () {
    Livewire::test(UsersForm::class)->assertStatus(200);
});

it('renders user form for editing', function () {
    $user = User::factory()->create(['name' => 'Editable User', 'email' => 'editable@example.com']);
    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->assertStatus(200)
        ->assertSet('name', 'Editable User');
});

it('can create a user with a role', function () {
    Livewire::test(UsersForm::class)
        ->set('name', 'New Person')
        ->set('email', 'person@example.com')
        ->set('password', 'password123')
        ->set('selectedRoles', ['manager'])
        ->call('save');

    $user = User::where('email', 'person@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('manager'))->toBeTrue();
    expect($user->is_admin)->toBeFalsy();
});

it('can create an admin user', function () {
    Livewire::test(UsersForm::class)
        ->set('name', 'Super Admin')
        ->set('email', 'super@example.com')
        ->set('password', 'password123')
        ->set('isAdmin', true)
        ->set('selectedRoles', ['manager'])
        ->call('save');

    expect(User::where('email', 'super@example.com')->first()->is_admin)->toBeTruthy();
});

it('can update a user and sync roles', function () {
    $user = User::factory()->create()->assignRole('manager');

    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->set('name', 'Renamed User')
        ->set('email', 'renamed@example.com')
        ->set('selectedRoles', [])
        ->call('save');

    expect($user->fresh()->name)->toBe('Renamed User');
    expect($user->fresh()->email)->toBe('renamed@example.com');
    expect($user->fresh()->roles)->toBeEmpty();
});

it('validates unique email on update', function () {
    $user = User::factory()->create(['email' => 'keep@example.com']);
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(UsersForm::class, ['id' => $user->id])
        ->set('email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

it('cannot delete own account', function () {
    Livewire::test(UsersIndex::class)
        ->call('confirmDelete', $this->admin->id)
        ->call('delete');

    expect(User::find($this->admin->id))->not->toBeNull();
});

it('can delete another user', function () {
    $user = User::factory()->create();

    Livewire::test(UsersIndex::class)
        ->call('confirmDelete', $user->id)
        ->call('delete');

    expect(User::find($user->id))->toBeNull();
});
