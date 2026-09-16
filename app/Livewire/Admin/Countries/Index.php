<?php

namespace App\Livewire\Admin\Countries;

use App\Concerns\HasPerPage;
use App\Models\Country;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $deletingId = null;

    /** @var array<int, int> */
    public array $selectedIds = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $country = Country::findOrFail($id);
        $newStatus = $country->status === 'active' ? 'inactive' : 'active';

        $country->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Country: {$country->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Country status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'country-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $country = Country::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Country: {$country->name}");
            $country->delete();
            $this->dispatch('notify', message: 'Country deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'country-delete');
    }

    /**
     * Ctrl/Cmd+click row selection or the row's own checkbox (see the view) —
     * toggles one country id in/out of the bulk-selection.
     */
    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

            return;
        }

        $this->selectedIds[] = $id;
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'country-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $countries = Country::whereIn('id', $this->selectedIds)->get();

        foreach ($countries as $country) {
            AdminActivity::log('deleted', "Country: {$country->name}");
            $country->delete();
        }

        $count = $countries->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('country', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'country-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.countries.index', [
            'countries' => Country::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->withCount('divisions')
                ->orderBy('name')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Countries', 'hidePageHeading' => true]);
    }
}
