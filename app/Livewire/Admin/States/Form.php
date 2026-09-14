<?php

namespace App\Livewire\Admin\States;

use App\Models\Country;
use App\Models\State;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $stateId = null;

    #[Validate('required|integer|exists:countries,id')]
    public ?int $countryId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $state = State::findOrFail($id);
            $this->stateId = $id;
            $this->countryId = $state->country_id;
            $this->name = $state->name;
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->stateId
            ? 'required|string|max:255|unique:states,name,'.$this->stateId.',id,country_id,'.$this->countryId
            : 'required|string|max:255|unique:states,name,NULL,id,country_id,'.$this->countryId;

        $this->validate($rules);

        $data = ['country_id' => $this->countryId, 'name' => $this->name];

        $creating = $this->stateId === null;

        if ($this->stateId) {
            State::findOrFail($this->stateId)->update($data);
            $this->dispatch('notify', message: 'State updated successfully');
        } else {
            State::create($data);
            $this->dispatch('notify', message: 'State created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "State: {$this->name}",
        );

        $this->redirect(route('admin.states'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.states.form', [
            'countries' => Country::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.admin', ['title' => $this->stateId ? 'Edit State' : 'New State']);
    }
}
