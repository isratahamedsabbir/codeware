<?php

namespace App\Livewire\Admin\EmailTemplates;

use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Services\EmailTemplateRenderer;
use App\Services\EmailTemplateService;
use App\Support\AdminActivity;
use App\Support\EnvFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class Index extends Component
{
    /**
     * The template used by sendTestEmail() — seeded by EmailTemplatesSeeder, editable
     * like any other template in the inventory on the left.
     */
    public const TEST_EMAIL_TEMPLATE_KEY = 'mail_settings_test';

    public ?int $selectedTemplateId = null;

    public string $templateKey = '';

    public string $name = '';

    public string $description = '';

    public string $subjectTemplate = '';

    public string $bodyTemplate = '';

    public string $variablesList = '';

    public bool $active = true;

    public string $previewVariablesJson = '';

    public string $previewSubject = '';

    public string $previewBody = '';

    /** @var array<string, string> */
    public array $mailSettings = [];

    public string $testEmailAddress = '';

    public function mount(): void
    {
        $firstTemplate = EmailTemplate::query()->orderBy('name')->first();

        if ($firstTemplate !== null) {
            $this->loadTemplate($firstTemplate->id);
        }

        $this->loadMailSettings();
    }

    /**
     * Editable mail .env keys. Lives here (not Settings → Env) because the only way to
     * know these are actually right is to send something with them — which this page
     * can do (sendTestEmail()) and the generic env form couldn't.
     *
     * @return array<string, array{label: string, type: string, options?: array<int, string>}>
     */
    public function mailFields(): array
    {
        return [
            'MAIL_MAILER' => ['label' => 'Mailer', 'type' => 'select', 'options' => ['smtp', 'log', 'sendmail', 'ses', 'postmark', 'resend']],
            'MAIL_HOST' => ['label' => 'SMTP Host', 'type' => 'text'],
            'MAIL_PORT' => ['label' => 'SMTP Port', 'type' => 'text'],
            'MAIL_USERNAME' => ['label' => 'SMTP Username', 'type' => 'text'],
            'MAIL_PASSWORD' => ['label' => 'SMTP Password', 'type' => 'password'],
            'MAIL_SCHEME' => ['label' => 'Encryption', 'type' => 'select', 'options' => ['null', 'tls', 'smtps']],
            'MAIL_FROM_ADDRESS' => ['label' => 'From Address', 'type' => 'text'],
            'MAIL_FROM_NAME' => ['label' => 'From Name', 'type' => 'text'],
        ];
    }

    protected function loadMailSettings(): void
    {
        $current = EnvFile::all();

        foreach (array_keys($this->mailFields()) as $key) {
            $this->mailSettings[$key] = $current[$key] ?? '';
        }
    }

    public function confirmSaveMailSettings(): void
    {
        $this->validate([
            'mailSettings.MAIL_MAILER' => 'required|in:smtp,log,sendmail,ses,postmark,resend',
            'mailSettings.MAIL_HOST' => 'nullable|string',
            'mailSettings.MAIL_PORT' => 'nullable|numeric',
            'mailSettings.MAIL_USERNAME' => 'nullable|string',
            'mailSettings.MAIL_PASSWORD' => 'nullable|string',
            'mailSettings.MAIL_SCHEME' => 'required|in:null,tls,smtps',
            'mailSettings.MAIL_FROM_ADDRESS' => 'required|email',
            'mailSettings.MAIL_FROM_NAME' => 'required|string',
        ]);

        $this->dispatch('open-modal', name: 'mail-settings-save-confirm');
    }

    public function saveMailSettings(): void
    {
        try {
            EnvFile::set($this->mailSettings);
        } catch (\RuntimeException $e) {
            $this->dispatch('close-modal', name: 'mail-settings-save-confirm');
            $this->dispatch('notify', message: 'Could not save mail settings: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Mail settings updated');

        $this->dispatch('close-modal', name: 'mail-settings-save-confirm');
        $this->dispatch('notify', message: 'Mail settings saved. Configuration cache cleared.');
    }

    /**
     * Sends TEST_EMAIL_TEMPLATE_KEY to testEmailAddress using whatever mail settings are
     * currently live — i.e. what was saved (and config:clear'd) on a previous request, not
     * whatever's unsaved in the mailSettings form right now. A prior saveMailSettings() call
     * always finished before this one starts, since each is its own Livewire round trip.
     */
    public function sendTestEmail(EmailTemplateService $service): void
    {
        $this->validate(
            ['testEmailAddress' => 'required|email'],
            [],
            ['testEmailAddress' => 'test email address'],
        );

        try {
            $sent = $service->send(self::TEST_EMAIL_TEMPLATE_KEY, $this->testEmailAddress, [
                'site_name' => Setting::get('site_name') ?: config('app.name'),
                'sent_at' => now()->toDayDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Test email failed: '.$e->getMessage());

            return;
        }

        if (! $sent) {
            $this->dispatch('notify', message: 'Could not send — the "'.self::TEST_EMAIL_TEMPLATE_KEY.'" template is missing or inactive.');

            return;
        }

        AdminActivity::log('updated', 'Sent a test email to '.$this->testEmailAddress);

        $this->dispatch('notify', message: 'Test email sent to '.$this->testEmailAddress.'.');
    }

    public function selectTemplate(int $templateId): void
    {
        $this->loadTemplate($templateId);
    }

    public function save(): void
    {
        abort_unless($this->selectedTemplateId !== null, 404);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:255'],
            'subjectTemplate' => ['required', 'string', 'max:191'],
            'bodyTemplate' => ['required', 'string'],
            'variablesList' => ['nullable', 'string'],
            'active' => ['required', 'boolean'],
        ]);

        $template = EmailTemplate::query()->findOrFail($this->selectedTemplateId);
        $template->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'subject_template' => $validated['subjectTemplate'],
            'body_template' => $validated['bodyTemplate'],
            'variables' => $this->parseVariablesList($validated['variablesList']),
            'active' => $validated['active'],
        ]);

        $this->dispatch('notify', message: 'Template updated successfully');
        $this->loadTemplate($template->id);
    }

    public function generatePreview(EmailTemplateRenderer $renderer): void
    {
        $variables = $this->decodePreviewVariables();

        $this->previewSubject = $renderer->renderSubject($this->subjectTemplate, $variables);
        $this->previewBody = $renderer->renderBody($this->bodyTemplate, $variables);
    }

    public function render(): View
    {
        return view('livewire.admin.email-templates.index', [
            'templates' => EmailTemplate::query()->orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => 'Email Templates']);
    }

    private function loadTemplate(int $templateId): void
    {
        $template = EmailTemplate::query()->findOrFail($templateId);

        $this->selectedTemplateId = $template->id;
        $this->templateKey = $template->key;
        $this->name = $template->name;
        $this->description = (string) ($template->description ?? '');
        $this->subjectTemplate = $template->subject_template;
        $this->bodyTemplate = $template->body_template;
        $this->variablesList = implode(', ', $template->variables ?? []);
        $this->active = (bool) $template->active;
        $this->previewVariablesJson = $this->buildPreviewJson($template->variables ?? []);
        $this->previewSubject = '';
        $this->previewBody = '';
    }

    /**
     * @return array<int, string>
     */
    private function parseVariablesList(string $variablesList): array
    {
        return collect(explode(',', $variablesList))
            ->map(fn (string $variable): string => trim($variable))
            ->filter(fn (string $variable): bool => $variable !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $variables
     */
    private function buildPreviewJson(array $variables): string
    {
        if ($variables === []) {
            return "{}\n";
        }

        $map = [];

        foreach ($variables as $variable) {
            $map[$variable] = $variable;
        }

        return (string) json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePreviewVariables(): array
    {
        $decoded = json_decode($this->previewVariablesJson, true);

        return is_array($decoded) ? $decoded : [];
    }
}
