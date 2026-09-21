<?php

namespace App\Livewire\Admin\Services;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Service;
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
        $service = Service::findOrFail($id);
        $newStatus = $service->status === 'active' ? 'inactive' : 'active';

        $service->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Service: {$service->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Service status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'service-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $service = Service::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Service: {$service->name}");
            $service->delete();
            $this->dispatch('notify', message: 'Service deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'service-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'service-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $services = Service::whereIn('id', $this->selectedIds)->get();

        foreach ($services as $service) {
            AdminActivity::log('deleted', "Service: {$service->name}");
            $service->delete();
        }

        $count = $services->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('service', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'service-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.services.index', [
            'services' => Service::query()
                ->with('creator')
                ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Services', 'hidePageHeading' => true]);
    }
}
