<?php

namespace App\Livewire\Admin\Contacts;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\SendsCustomEmail;
use App\Concerns\WithSearch;
use App\Models\Contact;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, SendsCustomEmail, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $viewingMessageId = null;

    public ?int $viewingContactId = null;

    public ?int $deletingId = null;

    public string $deleteConfirmation = '';

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

    /**
     * Deleting contacts is a developer-environment-only tool — checked
     * server-side too, since Livewire actions are callable without the button.
     */
    public function confirmDelete(int $id): void
    {
        abort_unless(app()->environment('developer'), 403);

        $this->deletingId = $id;
        $this->deleteConfirmation = '';
        $this->dispatch('open-modal', name: 'contact-delete');
    }

    public function delete(): void
    {
        abort_unless(app()->environment('developer'), 403);

        if (strtolower(trim($this->deleteConfirmation)) !== 'delete' || ! $this->deletingId) {
            return;
        }

        $contact = Contact::findOrFail($this->deletingId);
        AdminActivity::log('deleted', "Contact #{$contact->id}: {$contact->full_name}");
        $contact->delete();

        $this->deletingId = null;
        $this->deleteConfirmation = '';
        $this->viewingMessageId = null;
        $this->viewingContactId = null;
        $this->selectedIds = array_values(array_diff($this->selectedIds, [$contact->id]));
        $this->dispatch('close-modal', name: 'contact-delete');
        $this->dispatch('notify', message: 'Contact deleted successfully');
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
