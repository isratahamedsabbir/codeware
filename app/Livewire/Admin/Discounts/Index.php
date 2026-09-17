<?php

namespace App\Livewire\Admin\Discounts;

use App\Concerns\HasPerPage;
use App\Models\Discount;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    /** @var array<int, int> */
    public array $selectedIds = [];

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $discount = Discount::findOrFail($id);
        $newStatus = $discount->status === 'active' ? 'inactive' : 'active';

        $discount->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Discount: {$discount->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Discount status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'discount-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $discount = Discount::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Discount: {$discount->name}");
            $discount->delete();
            $this->dispatch('notify', message: 'Discount deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'discount-delete');
    }

    /**
     * Ctrl/Cmd+click row selection or the row's own checkbox (see the view) —
     * toggles one discount id in/out of the bulk-selection.
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

        $this->dispatch('open-modal', name: 'discount-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $discounts = Discount::whereIn('id', $this->selectedIds)->get();

        foreach ($discounts as $discount) {
            AdminActivity::log('deleted', "Discount: {$discount->name}");
            $discount->delete();
        }

        $count = $discounts->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('discount', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'discount-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.discounts.index', [
            'discounts' => Discount::query()
                ->with('creator')
                ->withCount('products')
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
                ->orderByDesc('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Discounts', 'hidePageHeading' => true]);
    }
}
