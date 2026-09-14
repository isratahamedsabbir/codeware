<?php

namespace App\Livewire\Admin\Divisions;

use App\Concerns\HasPerPage;
use App\Models\Division;
use App\Models\State;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $stateFilter = '';

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStateFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $division = Division::findOrFail($id);
        $newStatus = $division->status === 'active' ? 'inactive' : 'active';

        $division->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Division: {$division->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Division status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'division-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $division = Division::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Division: {$division->name}");
            $division->delete();
            $this->dispatch('notify', message: 'Division deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'division-delete');
    }

    public function render()
    {
        return view('livewire.admin.divisions.index', [
            'divisions' => Division::query()
                ->with('state.country')
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->stateFilter, fn ($q) => $q->where('state_id', $this->stateFilter))
                ->withCount('districts')
                ->orderBy('name')
                ->paginate($this->perPage),
            'states' => State::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.admin', ['title' => 'Divisions']);
    }
}
