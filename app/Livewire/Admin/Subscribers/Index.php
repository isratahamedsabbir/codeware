<?php

namespace App\Livewire\Admin\Subscribers;

use App\Concerns\HasPerPage;
use App\Models\Subscriber;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
        $this->dispatch('open-modal', name: 'subscriber-view');
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

    public function render()
    {
        return view('livewire.admin.subscribers.index', [
            'subscribers' => Subscriber::query()
                ->when($this->search, fn ($q) => $q->where('email', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Subscribers']);
    }
}
