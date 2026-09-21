<?php

namespace App\Livewire\Frontend;

use App\Models\Subscriber;
use Livewire\Component;

/**
 * Newsletter signup box, embedded in the storefront footer. Writes straight to
 * the same Subscriber model the admin's Subscribers inbox reads from — no API
 * round-trip needed since it renders server-side in the same app (same approach
 * as Frontend\ContactForm). Matches SubscriberController::store semantics:
 * already-subscribed emails are reported as such, and previously unsubscribed
 * emails are flipped back to subscribed rather than duplicated.
 */
class NewsletterSubscribe extends Component
{
    public string $email = '';

    public bool $done = false;

    public bool $already = false;

    protected function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
        ];
    }

    public function subscribe(): void
    {
        $validated = $this->validate();

        $subscriber = Subscriber::firstOrNew(['email' => $validated['email']]);

        if ($subscriber->exists && $subscriber->status === 'subscribed') {
            $this->already = true;
            $this->done = true;

            return;
        }

        $subscriber->status = 'subscribed';
        $subscriber->save();

        $this->reset('email');
        $this->done = true;
        $this->already = false;
    }

    public function render()
    {
        return view('livewire.frontend.newsletter-subscribe');
    }
}
