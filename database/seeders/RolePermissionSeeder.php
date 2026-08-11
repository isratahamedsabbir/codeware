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
            'view roles', 'manage roles',
            'view users', 'create users', 'update users', 'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(Permission::all());

        Role::findOrCreate('editor', 'web');
    }
}
