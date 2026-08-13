<?php

namespace App\Livewire\Admin\Roles;

use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'role-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $role = Role::findOrFail($this->deletingId);

            if ($role->name === 'admin') {
                $this->dispatch('notify', message: 'The admin role cannot be deleted');
            } else {
                $role->delete();
                AdminActivity::log('deleted', "Role: {$role->name}");
                $this->dispatch('notify', message: 'Role deleted successfully');
            }

            $this->deletingId = null;
        }

        $this->dispatch('close-modal', name: 'role-delete');
    }

    public function render()
    {
        return view('livewire.admin.roles.index', [
            'roles' => Role::query()
                ->withCount(['users', 'permissions'])
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
        ])->layout('layouts.admin', ['title' => 'Roles']);
    }
}
