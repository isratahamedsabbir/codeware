<?php

namespace App\Livewire\Admin\Portfolio\Skills;

use App\Concerns\HasTranslatableFields;
use App\Models\PortfolioSkill;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $skillId = null;

    public array $name = [];

    #[Validate('required|string|max:255')]
    public string $group = '';

    #[Validate('nullable|string|max:20')]
    public string $icon = '';

    public array $description = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $skill = PortfolioSkill::findOrFail($id);
            $this->skillId = $id;
            $this->hydrateTranslatable($skill, ['name', 'description']);
            $this->group = $skill->group;
            $this->icon = $skill->icon ?? '';
        }
    }

    public function save(): void
    {
        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]));

        $this->validate($rules);

        $data = [
            'name' => $this->translatablePayload('name'),
            'group' => $this->group,
            'icon' => $this->icon ?: null,
            'description' => $this->translatablePayload('description') ?: null,
        ];

        if ($this->skillId) {
            PortfolioSkill::findOrFail($this->skillId)->update($data);
            $this->dispatch('notify', message: 'Skill updated successfully');
        } else {
            // New skills stay inactive until switched on from the list —
            // status is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $data['sort_order'] = (int) PortfolioSkill::where('group', $this->group)->max('sort_order') + 1;
            PortfolioSkill::create($data);
            $this->dispatch('notify', message: 'Skill created successfully');
        }

        AdminActivity::log(
            $this->skillId ? 'updated' : 'created',
            "Portfolio Skill: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.portfolio-skills'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.portfolio.skills.form')
            ->layout('layouts.admin', ['title' => $this->skillId ? 'Edit Skill' : 'New Skill']);
    }
}
