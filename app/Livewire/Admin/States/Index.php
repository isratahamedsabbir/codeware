<?php

namespace App\Livewire\Admin\States;

use App\Concerns\HasPerPage;
use App\Models\Country;
use App\Models\State;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $countryFilter = '';

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCountryFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $state = State::findOrFail($id);
        $newStatus = $state->status === 'active' ? 'inactive' : 'active';

        $state->update(['status' => $newStatus]);

        AdminActivity::log('updated', "State: {$state->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'State status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'state-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $state = State::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "State: {$state->name}");
            $state->delete();
            $this->dispatch('notify', message: 'State deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'state-delete');
    }

    public function render()
    {
        return view('livewire.admin.states.index', [
            'states' => State::query()
                ->with('country')
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->countryFilter, fn ($q) => $q->where('country_id', $this->countryFilter))
                ->withCount('divisions')
                ->orderBy('name')
                ->paginate($this->perPage),
            'countries' => Country::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.admin', ['title' => 'States']);
    }
}
