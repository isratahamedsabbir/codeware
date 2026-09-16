<?php

namespace App\Livewire\Admin\Contacts;

use App\Concerns\HasPerPage;
use App\Models\Contact;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $viewingMessageId = null;

    /**
     * Contacts has no delete action (deliberately read-only) — this array
     * only ever backs the "Export" bulk-selection button, never a delete.
     *
     * @var array<int, int>
     */
    public array $selectedIds = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $id, string $status): void
    {
        $contact = Contact::findOrFail($id);
        $contact->update(['status' => $status]);
    }

    public function viewMessage(int $id): void
    {
        $this->viewingMessageId = $id;
    }

    public function closeMessage(): void
    {
        $this->viewingMessageId = null;
    }

    /**
     * Ctrl/Cmd+click row selection or the row's own checkbox (see the view) —
     * toggles one contact id in/out of the "Export" bulk-selection.
     */
    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

            return;
        }

        $this->selectedIds[] = $id;
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
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Contacts', 'hidePageHeading' => true]);
    }
}
