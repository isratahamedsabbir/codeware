<?php

namespace App\Livewire\Admin\EmailTemplates;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateRenderer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
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

    public function mount(): void
    {
        $firstTemplate = EmailTemplate::query()->orderBy('name')->first();

        if ($firstTemplate !== null) {
            $this->loadTemplate($firstTemplate->id);
        }
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
