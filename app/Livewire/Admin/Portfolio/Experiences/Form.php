<?php

namespace App\Livewire\Admin\Portfolio\Experiences;

use App\Concerns\HasTranslatableFields;
use App\Models\PortfolioExperience;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $experienceId = null;

    public array $role = [];

    public array $company = [];

    #[Validate('nullable|string|max:255')]
    public string $period = '';

    public array $description = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $experience = PortfolioExperience::findOrFail($id);
            $this->experienceId = $id;
            $this->hydrateTranslatable($experience, ['role', 'company', 'description']);
            $this->period = $experience->period ?? '';
        }
    }

    public function save(): void
    {
        $rules = array_merge($this->getRules(), $this->translatableRules([
            'role' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]));

        $this->validate($rules);

        $data = [
            'role' => $this->translatablePayload('role'),
            'company' => $this->translatablePayload('company') ?: null,
            'period' => $this->period ?: null,
            'description' => $this->translatablePayload('description') ?: null,
        ];

        if ($this->experienceId) {
            PortfolioExperience::findOrFail($this->experienceId)->update($data);
            $this->dispatch('notify', message: 'Experience updated successfully');
        } else {
            // New entries stay inactive until switched on from the list —
            // status is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $data['sort_order'] = (int) PortfolioExperience::max('sort_order') + 1;
            PortfolioExperience::create($data);
            $this->dispatch('notify', message: 'Experience created successfully');
        }

        AdminActivity::log(
            $this->experienceId ? 'updated' : 'created',
            "Portfolio Experience: {$this->primaryValue('role')}",
        );

        $this->redirect(route('admin.portfolio-experiences'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.portfolio.experiences.form')
            ->layout('layouts.admin', ['title' => $this->experienceId ? 'Edit Experience' : 'New Experience']);
    }
}
