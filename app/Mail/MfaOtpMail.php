<?php

namespace App\Mail;

use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The code mailed as a second factor: at sign-in, and once when the factor is
 * being switched on to prove the mailbox works. Issued through
 * App\Services\OtpService, which owns the expiry, attempt cap and resend limits.
 * The code is in the subject too, for the same reason as PasswordResetOtpMail.
 */
class MfaOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $code,
        public string $name,
        /** True when the code is for turning the factor on rather than signing in. */
        public bool $enrolling = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your :app sign-in code is :code', [
            'app' => config('app.name'),
            'code' => $this->code,
        ]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mfa-otp',
            with: [
                'code' => $this->code,
                'name' => $this->name,
                'enrolling' => $this->enrolling,
                'minutes' => OtpService::TTL_MINUTES,
            ],
        );
    }
}
