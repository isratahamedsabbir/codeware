<?php

namespace App\Livewire\Admin\Upazilas;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\District;
use App\Models\Upazila;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public string $districtFilter = '';

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDistrictFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $upazila = Upazila::findOrFail($id);
        $newStatus = $upazila->status === 'active' ? 'inactive' : 'active';

        $upazila->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Upazila: {$upazila->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Upazila status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'upazila-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $upazila = Upazila::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Upazila: {$upazila->name}");
            $upazila->delete();
            $this->dispatch('notify', message: 'Upazila deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'upazila-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'upazila-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $upazilas = Upazila::whereIn('id', $this->selectedIds)->get();

        foreach ($upazilas as $upazila) {
            AdminActivity::log('deleted', "Upazila: {$upazila->name}");
            $upazila->delete();
        }

        $count = $upazilas->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('upazila', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'upazila-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.upazilas.index', [
            'upazilas' => Upazila::query()
                ->with('district.division')
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->districtFilter, fn ($q) => $q->where('district_id', $this->districtFilter))
                ->orderBy('name')
                ->paginate($this->perPage),
            'districts' => District::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.admin', ['title' => 'Upazilas', 'hidePageHeading' => true]);
    }
}
