<?php

namespace App\Livewire\Frontend;

use App\Models\Contact;
use Livewire\Component;

/**
 * Public contact form, embedded on the "contact" page in every theme. Writes
 * straight to the same Contact model the admin's Contacts inbox reads from
 * (Contact::booted() already notifies admins on create) — no separate API
 * round-trip needed since this renders server-side in the same app.
 */
class ContactForm extends Component
{
    public string $full_name = '';

    public string $phone_number = '';

    public string $email = '';

    public string $subject = '';

    public string $message = '';

    public bool $sent = false;

    protected function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:30',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ];
    }

    public function send(): void
    {
        $validated = $this->validate();

        Contact::create($validated);

        $this->reset(['full_name', 'phone_number', 'email', 'subject', 'message']);
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.frontend.contact-form');
    }
}
