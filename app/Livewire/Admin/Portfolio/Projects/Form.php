<?php

namespace App\Livewire\Admin\Portfolio\Projects;

use App\Concerns\HasTranslatableFields;
use App\Models\PortfolioProject;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $projectId = null;

    public array $title = [];

    public array $description = [];

    #[Validate('nullable|string|max:20')]
    public string $icon = '';

    /** @var array<int, string> */
    public array $tech = [];

    #[Validate('nullable|string|max:255')]
    public string $stats = '';

    #[Validate('nullable|url|max:255')]
    public string $link = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $project = PortfolioProject::findOrFail($id);
            $this->projectId = $id;
            $this->hydrateTranslatable($project, ['title', 'description']);
            $this->icon = $project->icon ?? '';
            $this->tech = $project->tech ?? [];
            $this->stats = $project->stats ?? '';
            $this->link = $project->link ?? '';
        }

        if ($this->tech === []) {
            $this->tech = [''];
        }
    }

    public function addTech(): void
    {
        $this->tech[] = '';
    }

    public function removeTech(int $index): void
    {
        unset($this->tech[$index]);
        $this->tech = array_values($this->tech);
    }

    public function save(): void
    {
        $rules = array_merge($this->getRules(), $this->translatableRules([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]));

        $rules['tech'] = ['array'];
        // Each element its own rule string, not one 'nullable|string|max:255' —
        // a nested pipe-delimited string is read as a single rule *name* whose
        // parameters are whatever follows the first colon.
        $rules['tech.*'] = ['nullable', 'string', 'max:255'];

        $this->validate($rules);

        $data = [
            'title' => $this->translatablePayload('title'),
            'description' => $this->translatablePayload('description') ?: null,
            'icon' => $this->icon ?: null,
            'tech' => array_values(array_filter(array_map('trim', $this->tech), fn ($item) => $item !== '')) ?: null,
            'stats' => $this->stats ?: null,
            'link' => $this->link ?: null,
        ];

        if ($this->projectId) {
            PortfolioProject::findOrFail($this->projectId)->update($data);
            $this->dispatch('notify', message: 'Project updated successfully');
        } else {
            // New projects stay inactive until switched on from the list —
            // status is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $data['sort_order'] = (int) PortfolioProject::max('sort_order') + 1;
            PortfolioProject::create($data);
            $this->dispatch('notify', message: 'Project created successfully');
        }

        AdminActivity::log(
            $this->projectId ? 'updated' : 'created',
            "Portfolio Project: {$this->primaryValue('title')}",
        );

        $this->redirect(route('admin.portfolio-projects'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.portfolio.projects.form')
            ->layout('layouts.admin', ['title' => $this->projectId ? 'Edit Project' : 'New Project']);
    }
}
