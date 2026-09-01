<?php

namespace App\Livewire\Admin\Users;

use App\Concerns\HasPerPage;
use App\Models\User;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'user-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $user = User::findOrFail($this->deletingId);

            if ($user->id === auth()->id()) {
                $this->dispatch('notify', message: 'You cannot delete your own account');
            } else {
                $user->delete();
                AdminActivity::log('deleted', "User: {$user->email}");
                $this->dispatch('notify', message: 'User deleted successfully');
            }

            $this->deletingId = null;
        }

        $this->dispatch('close-modal', name: 'user-delete');
    }

    public function render()
    {
        return view('livewire.admin.users.index', [
            'users' => User::query()
                ->with('roles')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))
                ->when($this->roleFilter, fn ($q) => $q->role($this->roleFilter))
                ->orderBy('id')
                ->paginate($this->perPage),
            'roles' => Role::orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => 'Users']);
    }
}
