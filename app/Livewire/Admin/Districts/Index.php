<?php

namespace App\Livewire\Admin\Districts;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\District;
use App\Models\Division;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public string $divisionFilter = '';

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDivisionFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $district = District::findOrFail($id);
        $newStatus = $district->status === 'active' ? 'inactive' : 'active';

        $district->update(['status' => $newStatus]);

        AdminActivity::log('updated', "District: {$district->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'District status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'district-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $district = District::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "District: {$district->name}");
            $district->delete();
            $this->dispatch('notify', message: 'District deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'district-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'district-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $districts = District::whereIn('id', $this->selectedIds)->get();

        foreach ($districts as $district) {
            AdminActivity::log('deleted', "District: {$district->name}");
            $district->delete();
        }

        $count = $districts->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('district', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'district-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.districts.index', [
            'districts' => District::query()
                ->with(['division', 'country'])
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->divisionFilter, fn ($q) => $q->where('division_id', $this->divisionFilter))
                ->withCount('upazilas')
                ->orderBy('name')
                ->paginate($this->perPage),
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.admin', ['title' => 'Districts', 'hidePageHeading' => true]);
    }
}
