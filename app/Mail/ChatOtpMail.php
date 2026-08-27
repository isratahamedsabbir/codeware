<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChatOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $code,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your chat verification code: {$this->code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.chat-otp',
            with: [
                'code' => $this->code,
                'name' => $this->name,
            ],
        );
    }
}
