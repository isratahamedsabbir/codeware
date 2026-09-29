<?php

namespace App\Mail;

use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The code a customer types to set a new password — see
 * App\Services\PasswordResetService, which is what actually issues it. Carries
 * the code in the subject as well as the body: some clients and filters surface
 * the subject line when a message is filtered or previewed, and a one-time code
 * buried in a body is a code that never arrives.
 */
class PasswordResetOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $code,
        public string $name,
        /**
         * Why this code was sent, in the customer's own terms. An account made
         * for a guest's order is a different situation from a customer who
         * forgot their password, and the email should not blur them.
         */
        public string $reason = 'forgot',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your :app verification code is :code', [
            'app' => config('app.name'),
            'code' => $this->code,
        ]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-otp',
            with: [
                'code' => $this->code,
                'name' => $this->name,
                'reason' => $this->reason,
                'minutes' => OtpService::TTL_MINUTES,
            ],
        );
    }
}
