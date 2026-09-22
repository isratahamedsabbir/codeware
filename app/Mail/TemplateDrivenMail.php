<?php

namespace App\Mail;

use App\Support\EmailThemes;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplateDrivenMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, Attachment>  $attachments
     */
    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
        public ?string $viewName = null,
        public string $emailTheme = 'default',
        array $attachments = [],
    ) {
        // Assigned to the inherited (untyped) Mailable::$attachments property,
        // which buildAttachments() already knows how to send.
        $this->attachments = $attachments;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewName ?? EmailThemes::view($this->emailTheme),
            with: [
                'subjectLine' => $this->subjectLine,
                'bodyHtml' => $this->bodyHtml,
                'emailTheme' => $this->emailTheme,
            ],
        );
    }

    public function attachments(): array
    {
        return $this->attachments;
    }
}
