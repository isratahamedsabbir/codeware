<?php

namespace App\Livewire\Admin\Subscribers;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Subscriber;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'subscriber-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $subscriber = Subscriber::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Subscriber: {$subscriber->email}");
            $subscriber->delete();
            $this->dispatch('notify', message: 'Subscriber deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'subscriber-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'subscriber-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $subscribers = Subscriber::whereIn('id', $this->selectedIds)->get();

        foreach ($subscribers as $subscriber) {
            AdminActivity::log('deleted', "Subscriber: {$subscriber->email}");
            $subscriber->delete();
        }

        $count = $subscribers->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('subscriber', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'subscriber-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.subscribers.index', [
            'subscribers' => Subscriber::query()
                ->when($this->search, fn ($q) => $q->where('email', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Subscribers', 'hidePageHeading' => true]);
    }
}
