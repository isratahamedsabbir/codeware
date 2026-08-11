<?php

use App\Livewire\Admin\Permissions\Index as PermissionsIndex;
use App\Livewire\Admin\Roles\Form as RolesForm;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);

    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->permission = Permission::findOrCreate('view reports', 'web');
    $this->role = Role::findOrCreate('manager', 'web');
});

it('renders roles index', function () {
    Livewire::test(RolesIndex::class)->assertStatus(200);
});

it('displays roles in the table', function () {
    Livewire::test(RolesIndex::class)->assertSee('manager');
});

it('renders role form for creation', function () {
    Livewire::test(RolesForm::class)->assertStatus(200);
});

it('renders role form for editing', function () {
    Livewire::test(RolesForm::class, ['id' => $this->role->id])
        ->assertStatus(200)
        ->assertSet('name', 'manager');
});

it('can create a role with permissions', function () {
    Livewire::test(RolesForm::class)
        ->set('name', 'Content Editor')
        ->set('selectedPermissions', [$this->permission->name])
        ->call('save');

    $role = Role::where('name', 'content-editor')->first();

    expect($role)->not->toBeNull();
    expect($role->hasPermissionTo($this->permission->name))->toBeTrue();
});

it('can update a role and sync permissions', function () {
    $other = Permission::findOrCreate('export data', 'web');

    Livewire::test(RolesForm::class, ['id' => $this->role->id])
        ->set('name', 'Manager')
        ->set('selectedPermissions', [$other->name])
        ->call('save');

    expect($this->role->fresh()->hasPermissionTo($other->name))->toBeTrue();
    expect($this->role->fresh()->hasPermissionTo($this->permission->name))->toBeFalse();
});

it('cannot delete the admin role', function () {
    $adminRole = Role::findOrCreate('admin', 'web');

    Livewire::test(RolesIndex::class)
        ->call('confirmDelete', $adminRole->id)
        ->call('delete');

    expect(Role::where('name', 'admin')->exists())->toBeTrue();
});

it('can delete a non-protected role', function () {
    Livewire::test(RolesIndex::class)
        ->call('confirmDelete', $this->role->id)
        ->call('delete');

    expect(Role::where('name', 'manager')->exists())->toBeFalse();
});

it('renders permissions index', function () {
    Livewire::test(PermissionsIndex::class)
        ->assertStatus(200)
        ->assertSee('view reports');
});

it('can create a permission', function () {
    Livewire::test(PermissionsIndex::class)
        ->set('newName', 'Export Reports')
        ->call('create');

    expect(Permission::where('name', 'export-reports')->exists())->toBeTrue();
});
