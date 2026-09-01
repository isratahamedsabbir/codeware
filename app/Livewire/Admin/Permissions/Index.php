<?php

namespace App\Livewire\Admin\Permissions;

use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreateModal = false;

    #[Validate('required|string|max:255')]
    public string $newName = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset('newName');
        $this->showCreateModal = true;
        $this->dispatch('open-modal', name: 'permission-create');
    }

    public function create(): void
    {
        $this->validate();

        Permission::findOrCreate(Str::slug($this->newName), 'web');

        $this->dispatch('notify', message: 'Permission created successfully');
        $this->showCreateModal = false;
        $this->dispatch('close-modal', name: 'permission-create');
    }

    public function render()
    {
        return view('livewire.admin.permissions.index', [
            'permissions' => Permission::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->withCount('roles')
                ->orderBy('name')
                ->paginate(Setting::perPage()),
        ])->layout('layouts.admin', ['title' => 'Permissions']);
    }
}
