<?php

use App\Livewire\Admin\Permissions\Index as PermissionsIndex;
use App\Livewire\Admin\Roles\Form as RolesForm;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Models\User;
use App\Support\Mfa;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

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

it('ends the sessions of every holder when a role is deactivated', function () {
    $holder = User::factory()->create();
    $holder->assignRole($this->role);
    DB::table('sessions')->insert([
        'id' => 'session-holder', 'user_id' => $holder->id,
        'ip_address' => '127.0.0.1', 'user_agent' => 'test',
        'payload' => 'x', 'last_activity' => time(),
    ]);

    Livewire::test(RolesIndex::class)->call('toggleStatus', $this->role->id);

    expect(DB::table('sessions')->where('id', 'session-holder')->exists())->toBeFalse();
});

it('does not touch sessions when a role is reactivated', function () {
    $this->role->update(['status' => 'inactive']);

    $holder = User::factory()->create();
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

// ── The login-requirement switches ─────────────────────────────────────────

it('asks no role for MFA or a captcha until one is switched on', function () {
    foreach (Role::all() as $role) {
        expect((bool) $role->mfa_enabled)->toBeFalse()
            ->and((bool) $role->recaptcha_enabled)->toBeFalse();
    }
});

it('flips MFA and reCAPTCHA on a role from the table', function () {
    Livewire::test(RolesIndex::class)
        ->set('mfa.'.$this->role->id, true)
        ->set('recaptcha.'.$this->role->id, true);

    $role = $this->role->fresh();

    expect((bool) $role->mfa_enabled)->toBeTrue()
        ->and((bool) $role->recaptcha_enabled)->toBeTrue();
});

it('flips them back off again', function () {
    $this->role->update(['mfa_enabled' => true, 'recaptcha_enabled' => true]);

    Livewire::test(RolesIndex::class)
        ->set('mfa.'.$this->role->id, false)
        ->set('recaptcha.'.$this->role->id, false);

    $role = $this->role->fresh();

    expect((bool) $role->mfa_enabled)->toBeFalse()
        ->and((bool) $role->recaptcha_enabled)->toBeFalse();
});

it('one role switching on does not switch on the next one', function () {
    $other = Role::findOrCreate('editor', 'web');

    Livewire::test(RolesIndex::class)->set('mfa.'.$this->role->id, true);

    expect((bool) $this->role->fresh()->mfa_enabled)->toBeTrue()
        ->and((bool) $other->fresh()->mfa_enabled)->toBeFalse();
});

it('saves both switches from the role form', function () {
    Livewire::test(RolesForm::class)
        ->set('name', 'Support')
        ->set('mfaEnabled', true)
        ->set('recaptchaEnabled', true)
        ->call('save')
        ->assertHasNoErrors();

    $role = Role::findByName('Support', 'web');

    expect((bool) $role->mfa_enabled)->toBeTrue()
        ->and((bool) $role->recaptcha_enabled)->toBeTrue();
});

it('loads the switches when editing a role that already has them', function () {
    $this->role->update(['mfa_enabled' => true, 'recaptcha_enabled' => true]);

    Livewire::test(RolesForm::class, ['id' => $this->role->id])
        ->assertSet('mfaEnabled', true)
        ->assertSet('recaptchaEnabled', true);
});

it('leaves a role created from the form asking for nothing', function () {
    Livewire::test(RolesForm::class)
        ->set('name', 'Billing')
        ->call('save')
        ->assertHasNoErrors();

    $role = Role::findByName('Billing', 'web');

    expect((bool) $role->mfa_enabled)->toBeFalse()
        ->and((bool) $role->recaptcha_enabled)->toBeFalse();
});

it('does not ask an unrelated role when one role is switched on', function () {
    $this->role->update(['mfa_enabled' => true]);

    $holder = User::factory()->create();
    $holder->assignRole($this->role);

    $bystander = User::factory()->create();

    expect(Mfa::isRequiredFor($holder))->toBeTrue()
        ->and(Mfa::isRequiredFor($bystander))->toBeFalse();
});

it('can create a permission', function () {
    Livewire::test(PermissionsIndex::class)
        ->set('newName', 'Export Reports')
        ->call('create');

    expect(Permission::where('name', 'export-reports')->exists())->toBeTrue();
});
