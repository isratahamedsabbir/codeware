<?php

namespace App\Concerns;

use App\Mail\TemplateDrivenMail;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateRenderer;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\Mail;

/**
 * Adds a "Send Email" action to an admin Livewire component: either a one-off,
 * freeform email (not tied to any EmailTemplate row) or an email built from a
 * selected EmailTemplate row — sent using whatever mail settings are currently
 * live. Used by the Email Templates, Orders and Contacts admin pages; pair with
 * a `<flux:modal name="send-custom-email">` in the view (see
 * resources/views/livewire/admin/email-templates/index.blade.php for the
 * reference markup).
 */
trait SendsCustomEmail
{
    public string $customEmailTo = '';

    public string $customEmailTemplateKey = '';

    public string $customEmailVariables = '';

    public string $customEmailSubject = '';

    public string $customEmailDescription = '';

    /**
     * Active templates available in the "Send Email" modal. Empty key means the
     * freeform subject/description path.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, EmailTemplate>
     */
    public function customEmailTemplates(): \Illuminate\Database\Eloquent\Collection
    {
        return EmailTemplate::query()->where('active', true)->orderBy('name')->get();
    }

    public function updatedCustomEmailTemplateKey(?string $key): void
    {
        if ($key === null || $key === '') {
            $this->customEmailVariables = '';

            return;
        }

        $template = EmailTemplate::query()->where('key', $key)->first();

        if ($template === null) {
            $this->customEmailVariables = '';

            return;
        }

        $variables = $template->variables ?? [];

        if ($variables === []) {
            $this->customEmailVariables = "{}\n";

            return;
        }

        $map = [];

        foreach ($variables as $variable) {
            $map[$variable] = $variable;
        }

        $this->customEmailVariables = (string) json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function sendCustomEmail(): void
    {
        $usingTemplate = $this->customEmailTemplateKey !== '';

        $rules = [
            'customEmailTo' => ['required', 'email'],
            'customEmailTemplateKey' => ['nullable', 'string', 'max:191'],
        ];

        if ($usingTemplate) {
            $rules['customEmailVariables'] = ['nullable', 'string', 'json'];
        } else {
            $rules['customEmailSubject'] = ['required', 'string', 'max:191'];
            $rules['customEmailDescription'] = ['required', 'string'];
        }

        $attributes = [
            'customEmailTo' => 'email',
            'customEmailTemplateKey' => 'template',
            'customEmailVariables' => 'variables',
            'customEmailSubject' => 'subject',
            'customEmailDescription' => 'description',
        ];

        $validated = $this->validate($rules, [], $attributes);

        try {
            if ($usingTemplate) {
                $template = EmailTemplate::query()
                    ->where('key', $this->customEmailTemplateKey)
                    ->where('active', true)
                    ->first();

                if ($template === null) {
                    $this->dispatch('notify', message: 'Could not send — the selected template is missing or inactive.');

                    return;
                }

                $variables = json_decode($this->customEmailVariables !== '' ? $this->customEmailVariables : '{}', true);

                $renderer = app(EmailTemplateRenderer::class);

                $subject = $renderer->renderSubject($template->subject_template, is_array($variables) ? $variables : []);
                $body = $renderer->renderBody($template->body_template, is_array($variables) ? $variables : []);

                Mail::to($validated['customEmailTo'])->send(new TemplateDrivenMail($subject, $body));
            } else {
                Mail::to($validated['customEmailTo'])->send(new TemplateDrivenMail(
                    $validated['customEmailSubject'],
                    nl2br(e($validated['customEmailDescription'])),
                ));
            }
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Email failed: '.$e->getMessage());

            return;
        }

        AdminActivity::log('updated', 'Sent an email to '.$validated['customEmailTo']);

        $this->dispatch('notify', message: 'Email sent to '.$validated['customEmailTo'].'.');

        $this->reset(['customEmailTo', 'customEmailTemplateKey', 'customEmailVariables', 'customEmailSubject', 'customEmailDescription']);
    }
}