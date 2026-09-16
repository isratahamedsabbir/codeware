<?php

namespace App\Concerns;

use App\Mail\TemplateDrivenMail;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\Mail;

/**
 * Adds a "Send Email" action to an admin Livewire component: a one-off,
 * freeform email — not tied to any EmailTemplate row — sent using whatever
 * mail settings are currently live. Used by the Email Templates and Orders
 * admin pages; pair with a `<flux:modal name="send-custom-email">` in the
 * view (see resources/views/livewire/admin/email-templates/index.blade.php
 * for the reference markup).
 */
trait SendsCustomEmail
{
    public string $customEmailTo = '';

    public string $customEmailSubject = '';

    public string $customEmailDescription = '';

    public function sendCustomEmail(): void
    {
        $validated = $this->validate([
            'customEmailTo' => ['required', 'email'],
            'customEmailSubject' => ['required', 'string', 'max:191'],
            'customEmailDescription' => ['required', 'string'],
        ], [], [
            'customEmailTo' => 'email',
            'customEmailSubject' => 'subject',
            'customEmailDescription' => 'description',
        ]);

        try {
            Mail::to($validated['customEmailTo'])->send(new TemplateDrivenMail(
                $validated['customEmailSubject'],
                nl2br(e($validated['customEmailDescription'])),
            ));
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Email failed: '.$e->getMessage());

            return;
        }

        AdminActivity::log('updated', 'Sent an email to '.$validated['customEmailTo']);

        $this->dispatch('notify', message: 'Email sent to '.$validated['customEmailTo'].'.');

        $this->reset(['customEmailTo', 'customEmailSubject', 'customEmailDescription']);
    }
}
