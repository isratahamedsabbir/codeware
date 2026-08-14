<?php

use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Three admin tiers: Super Admin (is_admin=true), Admin (the 'admin' role — every
// permission), and Staff (the 'staff' role — content only). access-admin decides who
// gets into /admin/* at all; access-admin-system further restricts the system-level
// screens (Settings, Users, Roles/Permissions, Menu, Activity History, Localization,
// Contacts, Email Templates) to Admin/Super Admin — Staff is content-only.

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RolePermissionSeeder::class);

    $this->superAdmin = User::factory()->create(['is_admin' => true]);

    $this->adminRoleUser = User::factory()->create(['is_admin' => false]);
    $this->adminRoleUser->assignRole('admin');

    $this->staffUser = User::factory()->create(['is_admin' => false]);
    $this->staffUser->assignRole('staff');

    $this->systemRoutes = [
        'admin.settings', 'admin.email-templates', 'admin.contacts',
        'admin.roles', 'admin.permissions', 'admin.users',
        'admin.history', 'admin.languages', 'admin.translations', 'admin.menu',
    ];
});

it('seeds a staff role with content-only permissions and no system permissions', function () {
    $staff = Role::findByName('staff', 'web');
    $permissionNames = $staff->permissions->pluck('name')->all();

    expect($permissionNames)->toContain('view posts', 'create posts', 'update posts', 'delete posts')
        ->toContain('view products', 'view pages', 'view media', 'upload media')
        ->not->toContain('view settings', 'view users', 'view roles', 'manage roles', 'view file manager', 'manage file manager');
});

it('seeds the demo admin and staff users with the correct tier', function () {
    $this->seed(AdminSeeder::class);

    $admin = User::where('email', 'admin@admin.com')->firstOrFail();
    expect($admin->is_admin)->toBeTruthy()
        ->and($admin->hasRole('admin'))->toBeTrue();

    $staff = User::where('email', 'staff@admin.com')->firstOrFail();
    expect($staff->is_admin)->toBeFalsy()
        ->and($staff->hasRole('staff'))->toBeTrue();
});

it('lets all three tiers reach content routes', function () {
    foreach ([$this->superAdmin, $this->adminRoleUser, $this->staffUser] as $user) {
        $this->actingAs($user);
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.posts'))->assertOk();
        $this->get(route('admin.products'))->assertOk();
        $this->get(route('admin.pages'))->assertOk();
        $this->get(route('admin.media-library'))->assertOk();
    }
});

it('blocks staff from every system-only route', function () {
    $this->actingAs($this->staffUser);

    foreach ($this->systemRoutes as $routeName) {
        $this->get(route($routeName))->assertForbidden();
    }
});

it('lets admin and super admin reach every system-only route', function () {
    foreach ([$this->superAdmin, $this->adminRoleUser] as $user) {
        $this->actingAs($user);

        foreach ($this->systemRoutes as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }
});

it('blocks staff from the file manager, since it requires its own separate permission', function () {
    $this->actingAs($this->staffUser);

    $this->get(route('admin.file-manager'))->assertForbidden();
});

it('evaluates the access-admin-system gate correctly for each tier', function () {
    $this->actingAs($this->staffUser);
    expect(Gate::allows('access-admin-system'))->toBeFalse();

    $this->actingAs($this->adminRoleUser);
    expect(Gate::allows('access-admin-system'))->toBeTrue();

    $this->actingAs($this->superAdmin);
    expect(Gate::allows('access-admin-system'))->toBeTrue();
});

it('hides system-only sidebar items from staff but shows them to admin', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->actingAs($this->staffUser);
    $staffLabels = MenuItem::menuForCurrentUser()
        ->flatMap(fn ($item) => $item->is_group ? $item->children->pluck('label') : collect([$item->label]))
        ->all();
    expect($staffLabels)->not->toContain('Users', 'Roles', 'Settings', 'Menu');

    $this->actingAs($this->adminRoleUser);
    $adminLabels = MenuItem::menuForCurrentUser()
        ->flatMap(fn ($item) => $item->is_group ? $item->children->pluck('label') : collect([$item->label]))
        ->all();
    expect($adminLabels)->toContain('Users', 'Settings');
});

it('hides the Users link from the rendered sidebar for a staff user', function () {
    $this->seed(AdminMenuSeeder::class);
    $this->actingAs($this->staffUser);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Users', false);
});
