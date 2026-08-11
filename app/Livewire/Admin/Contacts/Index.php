<?php

namespace App\Livewire\Admin\Contacts;

use App\Models\Contact;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $viewingMessageId = null;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function updateStatus(int $id, string $status): void
    {
        $contact = Contact::findOrFail($id);
        $contact->update(['status' => $status]);
    }

    public function viewMessage(int $id): void
    {
        $this->viewingMessageId = $id;
        $this->dispatch('open-modal', name: 'view-message');
    }

    public function closeMessage(): void
    {
        $this->viewingMessageId = null;
    }

    public function render()
    {
        return view('livewire.admin.contacts.index', [
            'contacts' => Contact::query()
                ->when($this->search, fn ($q) => $q
                    ->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('subject', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Contacts']);
    }
}
