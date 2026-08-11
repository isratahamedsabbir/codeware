<?php

namespace App\Livewire\Admin\Departments;

use App\Models\Department;
use Livewire\Component;
use Livewire\WithPagination;

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
        $this->dispatch('open-modal', name: 'department-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Department::findOrFail($this->deletingId)->delete();
            $this->dispatch('notify', message: 'Department deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'department-delete');
    }

    public function render()
    {
        return view('livewire.admin.departments.index', [
            'departments' => Department::query()
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->orderBy('name->en')
                ->paginate(15),
        ])->layout('layouts.admin', ['title' => 'Departments']);
    }
}
