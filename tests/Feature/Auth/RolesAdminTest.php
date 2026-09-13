<?php

use App\Livewire\Admin\Permissions\Index as PermissionsIndex;
use App\Livewire\Admin\Roles\Form as RolesForm;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

it('can deactivate and reactivate a non-protected role', function () {
    Livewire::test(RolesIndex::class)->call('toggleStatus', $this->role->id);

    expect($this->role->fresh()->status)->toBe('inactive');

    Livewire::test(RolesIndex::class)->call('toggleStatus', $this->role->id);

    expect($this->role->fresh()->status)->toBe('active');
});

it('cannot deactivate the admin role', function () {
    $adminRole = Role::findOrCreate('admin', 'web');

    Livewire::test(RolesIndex::class)->call('toggleStatus', $adminRole->id);

    expect($adminRole->fresh()->status)->toBe('active');
});

it('ends the sessions of every non-super-admin holder when a role is deactivated', function () {
    $holder = User::factory()->create(['is_admin' => false]);
    $holder->assignRole($this->role);
    DB::table('sessions')->insert([
        'id' => 'session-holder', 'user_id' => $holder->id,
        'ip_address' => '127.0.0.1', 'user_agent' => 'test',
        'payload' => 'x', 'last_activity' => time(),
    ]);

    $superAdminHolder = User::factory()->create(['is_admin' => true]);
    $superAdminHolder->assignRole($this->role);
    DB::table('sessions')->insert([
        'id' => 'session-super-admin', 'user_id' => $superAdminHolder->id,
        'ip_address' => '127.0.0.1', 'user_agent' => 'test',
        'payload' => 'x', 'last_activity' => time(),
    ]);

    Livewire::test(RolesIndex::class)->call('toggleStatus', $this->role->id);

    expect(DB::table('sessions')->where('id', 'session-holder')->exists())->toBeFalse();
    expect(DB::table('sessions')->where('id', 'session-super-admin')->exists())->toBeTrue();
});

it('does not touch sessions when a role is reactivated', function () {
    $this->role->update(['status' => 'inactive']);

    $holder = User::factory()->create(['is_admin' => false]);
    $holder->assignRole($this->role);
    DB::table('sessions')->insert([
        'id' => 'session-reactivate', 'user_id' => $holder->id,
        'ip_address' => '127.0.0.1', 'user_agent' => 'test',
        'payload' => 'x', 'last_activity' => time(),
    ]);

    Livewire::test(RolesIndex::class)->call('toggleStatus', $this->role->id);

    expect($this->role->fresh()->status)->toBe('active');
    expect(DB::table('sessions')->where('id', 'session-reactivate')->exists())->toBeTrue();
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
