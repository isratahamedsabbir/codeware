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
 * Collects name/email/subject/message. phone_number is a NOT NULL column on
 * Contact but isn't meaningful to collect from this short form, so it is
 * filled with a placeholder default rather than shown as a field.
 */
class ContactForm extends Component
{
    public string $full_name = '';

    public string $email = '';

    public string $subject = '';

    public string $message = '';

    public bool $sent = false;

    /** Optional message placeholder a theme can pass in (its own wording). */
    public string $messagePlaceholder = '';

    protected function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ];
    }

    public function send(): void
    {
        $validated = $this->validate();

        Contact::create([
            ...$validated,
            'phone_number' => '',
        ]);

        $this->reset(['full_name', 'email', 'subject', 'message']);
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.frontend.contact-form');
    }
}
