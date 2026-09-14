<?php

namespace App\Livewire\Admin\Upazilas;

use App\Models\District;
use App\Models\Upazila;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $upazilaId = null;

    #[Validate('required|integer|exists:districts,id')]
    public ?int $districtId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $upazila = Upazila::findOrFail($id);
            $this->upazilaId = $id;
            $this->districtId = $upazila->district_id;
            $this->name = $upazila->name;
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->upazilaId
            ? 'required|string|max:255|unique:upazilas,name,'.$this->upazilaId.',id,district_id,'.$this->districtId
            : 'required|string|max:255|unique:upazilas,name,NULL,id,district_id,'.$this->districtId;

        $this->validate($rules);

        $data = ['district_id' => $this->districtId, 'name' => $this->name];

        $creating = $this->upazilaId === null;

        if ($this->upazilaId) {
            Upazila::findOrFail($this->upazilaId)->update($data);
            $this->dispatch('notify', message: 'Upazila updated successfully');
        } else {
            Upazila::create($data);
            $this->dispatch('notify', message: 'Upazila created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Upazila: {$this->name}",
        );

        $this->redirect(route('admin.upazilas'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.upazilas.form', [
            'districts' => District::with('division')->orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => $this->upazilaId ? 'Edit Upazila' : 'New Upazila']);
    }
}
