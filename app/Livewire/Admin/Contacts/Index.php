<?php

namespace App\Livewire\Admin\Contacts;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\SendsCustomEmail;
use App\Concerns\WithSearch;
use App\Models\Contact;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, SendsCustomEmail, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $viewingMessageId = null;

    public ?int $viewingContactId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * "Read" is a one-way status — once set, a contact can never be flipped
     * back to "unread" (matches the UI, which drops the dropdown for a
     * static badge as soon as a contact is read).
     */
    public function updateStatus(int $id, string $status): void
    {
        $contact = Contact::findOrFail($id);

        if ($contact->status === 'read') {
            return;
        }

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

    public function showContact(int $id): void
    {
        $this->viewingContactId = $id;
        $this->dispatch('open-modal', name: 'contact-view');
    }

    /**
     * Opens the SendsCustomEmail modal pre-filled with this contact's email
     * — the "Reply by Email" button inside the view modal.
     */
    public function openCustomEmailFor(int $id): void
    {
        $contact = Contact::findOrFail($id);
        $this->customEmailTo = $contact->email;
        $this->customEmailSubject = 'Re: '.$contact->subject;
        $this->dispatch('close-modal', name: 'contact-view');
        $this->dispatch('open-modal', name: 'send-custom-email');
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
