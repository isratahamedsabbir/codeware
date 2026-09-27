<?php

namespace App\Livewire\Admin\Bookings;

use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Booking;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The service bookings inbox — every "request to be contacted" a visitor has sent
 * from a service card on the storefront, newest first.
 *
 * Read-only in the way an inbox is: a booking is something a person said, so
 * there is no edit form. The only writes are the two that are really about the
 * conversation rather than the record — marking it dealt with, and deleting it.
 *
 * The status is a two-state toggle rather than the enum it is, because a booking
 * has exactly one transition: nobody has got to it, or they have. "Cancelled" and
 * "no-show" were considered and left out — they are notes for the reply email,
 * not states of the request itself, and a status nobody acts on is a filter that
 * hides rows for no reason.
 */
class Index extends Component
{
    use HasPerPage, WithPagination, WithSearch;

    /** New first, because the reason to open this screen is the newest request. */
    public string $statusFilter = '';

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Mark a booking dealt with, or put it back if it was marked by mistake.
     *
     * Not a blind flip on a status column: the booking's identity is named in
     * the log line, so there is a record of who was dealt with and when even
     * after the row is eventually deleted.
     */
    public function toggleStatus(int $id): void
    {
        $booking = Booking::with('service')->findOrFail($id);
        $newStatus = $booking->status === Booking::STATUS_NEW
            ? Booking::STATUS_COMPLETED
            : Booking::STATUS_NEW;

        $booking->update(['status' => $newStatus]);

        AdminActivity::log(
            'updated',
            "Booking #{$booking->id} ({$booking->full_name}) marked ".($newStatus === Booking::STATUS_COMPLETED ? 'completed' : 'new')
        );

        $this->dispatch('notify', message: 'Booking status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'booking-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $booking = Booking::with('service')->findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Booking #{$booking->id} from {$booking->full_name}");

            // Soft delete, so a request that was in fact needed is recoverable
            // rather than gone. Bookings are also personal data — an email address
            // and sometimes a phone number — so keeping them soft-deleted rather
            // than erased is also the more careful default.
            $booking->delete();

            $this->dispatch('notify', message: 'Booking deleted');
            $this->deletingId = null;
        }

        $this->dispatch('close-modal', name: 'booking-delete');
    }

    public function render()
    {
        return view('livewire.admin.bookings.index', [
            'bookings' => Booking::query()
                ->with('service')
                // A soft-deleted service keeps its bookings, and a booking whose
                // service is gone still has a name, an email and a message worth
                // reading — so the service is loaded, not required, and the table
                // falls back to "deleted service" rather than dropping the row.
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('message', 'like', "%{$this->search}%")
                        ->orWhereHas('service', fn ($q) => $q->where('name->en', 'like', "%{$this->search}%"));
                }))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate($this->perPage),

            // Drives the count next to the New filter. Read on every render
            // rather than cached: it is one indexed COUNT over a table that
            // gets a handful of rows a week, and a stale badge that disagrees
            // with the list underneath it is worse than the query.
            'newCount' => Booking::new()->count(),
        ])->layout('layouts.admin', ['title' => 'Bookings', 'hidePageHeading' => true]);
    }
}
