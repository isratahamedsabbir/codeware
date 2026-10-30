<?php

namespace App\Livewire\Admin\Districts;

use App\Concerns\StaysOnForm;
use App\Models\District;
use App\Models\Division;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use StaysOnForm;

    public ?int $districtId = null;

    #[Validate('required|integer|exists:divisions,id')]
    public ?int $divisionId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $district = District::findOrFail($id);
            $this->districtId = $id;
            $this->divisionId = $district->division_id;
            $this->name = $district->name;
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->districtId
            ? 'required|string|max:255|unique:districts,name,'.$this->districtId.',id,division_id,'.$this->divisionId
            : 'required|string|max:255|unique:districts,name,NULL,id,division_id,'.$this->divisionId;

        $this->validate($rules);

        $data = ['division_id' => $this->divisionId, 'name' => $this->name];

        $creating = $this->districtId === null;

        if ($this->districtId) {
            District::findOrFail($this->districtId)->update($data);
            $this->dispatch('notify', message: 'District updated successfully');
        } else {
            $this->districtId = District::create($data)->id;
            $this->dispatch('notify', message: 'District created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "District: {$this->name}",
        );

        $this->stayOnForm($creating, 'admin.districts.edit', $this->districtId);
    }

    public function render()
    {
        return view('livewire.admin.districts.form', [
            'divisions' => Division::with('country')->orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => $this->districtId ? 'Edit District' : 'New District']);
    }
}
