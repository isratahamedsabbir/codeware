<?php

namespace App\Livewire\Admin\ShippingMethods;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\ShippingMethod;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $method = ShippingMethod::findOrFail($id);
        $newStatus = $method->status === 'active' ? 'inactive' : 'active';

        $method->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Shipping Method: {$method->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Shipping method status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'shipping-method-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $method = ShippingMethod::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Shipping Method: {$method->name}");
            $method->delete();
            $this->dispatch('notify', message: 'Shipping method deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'shipping-method-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'shipping-method-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $methods = ShippingMethod::whereIn('id', $this->selectedIds)->get();

        foreach ($methods as $method) {
            AdminActivity::log('deleted', "Shipping Method: {$method->name}");
            $method->delete();
        }

        $count = $methods->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('shipping method', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'shipping-method-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.shipping-methods.index', [
            'shippingMethods' => ShippingMethod::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Shipping Methods', 'hidePageHeading' => true]);
    }
}
