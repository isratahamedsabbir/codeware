<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly JobApplication $application,
    ) {}

    public function envelope(): Envelope
    {
        $jobTitle = $this->application->job
            ? $this->application->job->getTranslation('title', 'en', useFallbackLocale: true)
            : 'the position';

        return new Envelope(
            subject: 'Application Received — ' . $jobTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.application-submitted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
