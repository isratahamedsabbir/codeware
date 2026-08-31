<?php

namespace App\Livewire\Frontend;

use App\Models\Contact;
use Livewire\Component;

/**
 * Public contact form, embedded on the "contact" page in every theme. Writes
 * straight to the same Contact model the admin's Contacts inbox reads from
 * (Contact::booted() already notifies admins on create) — no separate API
 * round-trip needed since this renders server-side in the same app.
 *
 * Only asks for name/email/message — phone_number and subject are NOT NULL
 * columns on Contact but aren't meaningful to collect from this short form,
 * so they're filled with a placeholder default rather than shown as fields.
 */
class ContactForm extends Component
{
    public string $full_name = '';

    public string $email = '';

    public string $message = '';

    public bool $sent = false;

    protected function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ];
    }

    public function send(): void
    {
        $validated = $this->validate();

        Contact::create([
            ...$validated,
            'phone_number' => '',
            'subject' => 'Website Contact Form',
        ]);

        $this->reset(['full_name', 'email', 'message']);
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.frontend.contact-form');
    }
}
