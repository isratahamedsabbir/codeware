<?php

namespace App\Livewire\Admin\Divisions;

use App\Models\Division;
use App\Models\State;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $divisionId = null;

    #[Validate('required|integer|exists:states,id')]
    public ?int $stateId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $division = Division::findOrFail($id);
            $this->divisionId = $id;
            $this->stateId = $division->state_id;
            $this->name = $division->name;
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->divisionId
            ? 'required|string|max:255|unique:divisions,name,'.$this->divisionId.',id,state_id,'.$this->stateId
            : 'required|string|max:255|unique:divisions,name,NULL,id,state_id,'.$this->stateId;

        $this->validate($rules);

        $data = ['state_id' => $this->stateId, 'name' => $this->name];

        $creating = $this->divisionId === null;

        if ($this->divisionId) {
            Division::findOrFail($this->divisionId)->update($data);
            $this->dispatch('notify', message: 'Division updated successfully');
        } else {
            Division::create($data);
            $this->dispatch('notify', message: 'Division created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Division: {$this->name}",
        );

        $this->redirect(route('admin.divisions'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.divisions.form', [
            'states' => State::with('country')->orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => $this->divisionId ? 'Edit Division' : 'New Division']);
    }
}
