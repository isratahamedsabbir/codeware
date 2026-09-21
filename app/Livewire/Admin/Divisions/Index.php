<?php

namespace App\Livewire\Admin\Divisions;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Country;
use App\Models\Division;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public string $countryFilter = '';

    public ?int $deletingId = null;

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

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'division-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $divisions = Division::whereIn('id', $this->selectedIds)->get();

        foreach ($divisions as $division) {
            AdminActivity::log('deleted', "Division: {$division->name}");
            $division->delete();
        }

        $count = $divisions->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('division', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'division-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.divisions.index', [
            'divisions' => Division::query()
                ->with('country')
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->countryFilter, fn ($q) => $q->where('country_id', $this->countryFilter))
                ->withCount('districts')
                ->orderBy('name')
                ->paginate($this->perPage),
            'countries' => Country::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.admin', ['title' => 'Divisions', 'hidePageHeading' => true]);
    }
}
