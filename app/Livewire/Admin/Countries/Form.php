<?php

namespace App\Livewire\Admin\Countries;

use App\Models\Country;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $countryId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $country = Country::findOrFail($id);
            $this->countryId = $id;
            $this->name = $country->name;
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->countryId
            ? 'required|string|max:255|unique:countries,name,'.$this->countryId
            : 'required|string|max:255|unique:countries,name';

        $this->validate($rules);

        $data = ['name' => $this->name];

        $creating = $this->countryId === null;

        if ($this->countryId) {
            Country::findOrFail($this->countryId)->update($data);
            $this->dispatch('notify', message: 'Country updated successfully');
        } else {
            Country::create($data);
            $this->dispatch('notify', message: 'Country created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Country: {$this->name}",
        );

        $this->redirect(route('admin.countries'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.countries.form')
            ->layout('layouts.admin', ['title' => $this->countryId ? 'Edit Country' : 'New Country']);
    }
}
