<?php

namespace App\Services;

use App\Mail\TemplateDrivenMail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;

class EmailTemplateService
{
    public function __construct(
        private readonly EmailTemplateRenderer $renderer,
    ) {}

    /**
     * @param  array<string, mixed>  $variables
     */
    public function send(string $key, string $recipient, array $variables = []): bool
    {
        $template = EmailTemplate::query()
            ->where('key', $key)
            ->where('active', true)
            ->first();

        if ($template === null || trim($recipient) === '') {
            return false;
        }

        $subject = $this->renderer->renderSubject($template->subject_template, $variables);
        $body = $this->renderer->renderBody($template->body_template, $variables);

        Mail::to($recipient)->send(new TemplateDrivenMail($subject, $body));

        return true;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{subject: string, body: string}|null
     */
    public function preview(string $key, array $variables = []): ?array
    {
        $template = EmailTemplate::query()->where('key', $key)->first();

        if ($template === null) {
            return null;
        }

        return [
            'subject' => $this->renderer->renderSubject($template->subject_template, $variables),
            'body' => $this->renderer->renderBody($template->body_template, $variables),
        ];
    }
}
