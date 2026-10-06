<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles and permissions.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'view posts', 'create posts', 'update posts', 'delete posts',
            'view post categories', 'create post categories', 'update post categories', 'delete post categories',
            'view tags', 'create tags', 'update tags', 'delete tags',
            'view pages', 'create pages', 'update pages', 'delete pages',
            'view products', 'create products', 'update products', 'delete products',
            'view product categories', 'create product categories', 'update product categories', 'delete product categories',
            'view media', 'upload media', 'delete media',
            'view settings', 'update settings',
            'view contacts', 'delete contacts',
            'view reviews', 'update reviews', 'delete reviews',
            'view roles', 'manage roles',
            'view users', 'create users', 'update users', 'delete users',
            'view file manager', 'manage file manager',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(Permission::all());

        // Staff: content-only. No access to Settings, Users, Roles/Permissions, Menu,
        // Activity History, Localization, Contacts, Comments, or the File Manager —
        // those stay Admin/Super Admin only (see access-admin-system gate in
        // AppServiceProvider). Ships switched off like the storefront-facing tiers:
        // an admin enables it deliberately from Admin → Roles.
        $staff = $this->createInactiveRole('staff');
        $staff->syncPermissions([
            'view dashboard',
            'view posts', 'create posts', 'update posts', 'delete posts',
            'view post categories', 'create post categories', 'update post categories', 'delete post categories',
            'view tags', 'create tags', 'update tags', 'delete tags',
            'view pages', 'create pages', 'update pages', 'delete pages',
            'view products', 'create products', 'update products', 'delete products',
            'view product categories', 'create product categories', 'update product categories', 'delete product categories',
            'view media', 'upload media', 'delete media',
        ]);

        // Vendor: no admin-panel permissions at all — this role only gates entry to
        // the separate Vendor Portal (see access-vendor-portal in AppServiceProvider),
        // it grants nothing under /admin/*.
        $this->createInactiveRole('vendor');

        // Delivery boy: same idea as Vendor — no admin-panel permissions, only
        // gates entry to the separate Delivery Portal (see access-delivery-portal
        // in AppServiceProvider). Never combined with admin/staff/vendor.
        $this->createInactiveRole('delivery_boy');

        // Customer: the default tier for everyone who registers through the public
        // site/API (see CreateNewUser) — no admin-panel permissions, access-admin
        // already excludes it. Every user belongs to some role; this is the
        // catch-all for the ones that aren't admin/staff/vendor.
        $this->createInactiveRole('customer');

        // Every run leaves exactly one live tier: admin. Any other role (the ones above
        // and any custom role) is switched off, even one an admin had enabled.
        $admin->update(['status' => 'active']);
        Role::where('name', '!=', 'admin')->update(['status' => 'inactive']);

        // 2FA is on for the admin role by default (it is also forced by
        // security.require_admin_mfa); every other role stays opt-in from Admin → Roles.
        Role::query()->update(['mfa_enabled' => false]);
        $admin->update(['mfa_enabled' => true]);
    }

    /**
     * Creates one of the non-admin roles (staff/vendor/delivery_boy/customer)
     * switched off, so a fresh install ships with only the admin tier live: an
     * admin enables each one deliberately from Admin → Roles, the same
     * "inactive until switched on" rule every other create-form in the panel
     * follows.
     */
    private function createInactiveRole(string $name): Role
    {
        $role = Role::findOrCreate($name, 'web');

        if ($role->wasRecentlyCreated) {
            $role->update(['status' => 'inactive']);
        }

        return $role;
    }
}
