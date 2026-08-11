<?php

namespace App\Livewire\Admin\Roles;

use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    public ?int $roleId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public array $selectedPermissions = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $role = Role::findOrFail($id);
            $this->roleId = $id;
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        }
    }

    public function toggleGroup(string $group): void
    {
        $names = collect($this->permissionGroups()[$group]['permissions'] ?? [])->pluck('name')->toArray();

        $allSelected = collect($names)->every(fn (string $name) => in_array($name, $this->selectedPermissions));

        $this->selectedPermissions = $allSelected
            ? array_values(array_diff($this->selectedPermissions, $names))
            : array_values(array_unique(array_merge($this->selectedPermissions, $names)));
    }

    public function save(): void
    {
        $this->validate();

        $name = Str::slug($this->name);

        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);
            if ($role->name !== 'admin') {
                $role->update(['name' => $name, 'guard_name' => 'web']);
            }
            $role->syncPermissions($this->selectedPermissions);
            $this->dispatch('notify', message: 'Role updated successfully');
        } else {
            $role = Role::create(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($this->selectedPermissions);
            $this->dispatch('notify', message: 'Role created successfully');
        }

        $this->redirect(route('admin.roles'), navigate: true);
    }

    public function permissionGroups(): array
    {
        $groups = [];

        foreach (Permission::orderBy('name')->get() as $permission) {
            $parts = explode(' ', $permission->name);
            $group = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'general';
            $groups[$group]['label'] = Str::title($group);
            $groups[$group]['permissions'][] = $permission;
        }

        ksort($groups);

        return $groups;
    }

    public function render()
    {
        return view('livewire.admin.roles.form', [
            'permissionGroups' => $this->permissionGroups(),
            'role' => $this->roleId ? Role::findOrFail($this->roleId) : null,
        ])->layout('layouts.admin', ['title' => $this->roleId ? 'Edit Role' : 'New Role']);
    }
}
