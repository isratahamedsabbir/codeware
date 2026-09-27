<?php

namespace App\Livewire\Frontend;

use App\Models\Booking;
use App\Models\Service;
use Livewire\Component;

/**
 * The "Book this" form on a service card.
 *
 * Written server-side into the same Booking model the admin Bookings screen reads
 * — no API round-trip, since a theme renders inside this same app. Same approach,
 * and for the same reason, as Frontend\ContactForm.
 *
 * The chosen service arrives as a public property rather than as a select: a
 * visitor books the service they are looking at, so offering a dropdown of the
 * other ones inside that card is a way to get the wrong row on the record. The id
 * is re-resolved against the active services on submit, so a tampered-with
 * payload cannot book an inactive or deleted service.
 */
class BookService extends Component
{
    public int $serviceId = 0;

    public string $fullName = '';

    public string $email = '';

    public string $phoneNumber = '';

    public string $message = '';

    public bool $sent = false;

    /**
     * The service being booked, or null when the id is not an active service.
     * Null everywhere in this component, and the view withholds the form, so a
     * service retired between the page render and a Livewire re-render leaves an
     * inert card rather than one that cannot be submitted.
     */
    public function getServiceProperty(): ?Service
    {
        return Service::active()->find($this->serviceId);
    }

    protected function rules(): array
    {
        return [
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            // Optional. Left as a free-form string rather than digits: phone
            // numbers are written "+880 1711-000000" as often as not, and a
            // numeric rule would reject the formatting that makes it readable.
            'phoneNumber' => ['nullable', 'string', 'max:32'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected $validationAttributes = [
        'fullName' => 'name',
        'phoneNumber' => 'phone number',
    ];

    public function book(): void
    {
        $validated = $this->validate();

        $service = $this->service;

        if ($service === null) {
            // The service was retired between the page render and this submit.
            // Refusing is the only honest answer: storing the request anyway would
            // put a booking against something the visitor can no longer see.
            $this->addError('serviceId', 'This service is no longer available.');

            return;
        }

        Booking::create([
            'service_id' => $service->id,
            'full_name' => $validated['fullName'],
            'email' => $validated['email'],
            'phone_number' => $validated['phoneNumber'] ?? null,
            'message' => $validated['message'] ?? null,
        ]);

        $this->reset(['fullName', 'email', 'phoneNumber', 'message']);
        $this->sent = true;

        $this->dispatch('booked');
    }

    public function render()
    {
        return view('livewire.frontend.book-service', [
            'service' => $this->service,
        ]);
    }
}
