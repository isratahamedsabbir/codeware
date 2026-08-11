<?php

namespace App\Livewire\Admin\Districts;

use App\Models\District;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $districtId = null;

    #[Validate('required|string|max:255')]
    public string $name_en = '';

    #[Validate('nullable|string|max:255')]
    public string $name_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    public int $sort_order = 0;

    #[Validate('in:active,inactive')]
    public string $status = 'active';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $district = District::findOrFail($id);
            $this->districtId  = $id;
            $this->name_en     = $district->getTranslation('name', 'en', false) ?? '';
            $this->name_bn     = $district->getTranslation('name', 'bn', false) ?? '';
            $this->slug        = $district->slug;
            $this->sort_order  = $district->sort_order;
            $this->status      = $district->status;
        }
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->districtId
            ? 'required|string|max:255|unique:districts,slug,' . $this->districtId
            : 'required|string|max:255|unique:districts,slug';

        $this->validate($rules);

        $data = [
            'name'       => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug'       => $this->slug,
            'sort_order' => $this->sort_order,
            'status'     => $this->status,
        ];

        if ($this->districtId) {
            District::findOrFail($this->districtId)->update($data);
            $this->dispatch('notify', message: 'District updated successfully');
        } else {
            District::create($data);
            $this->dispatch('notify', message: 'District created successfully');
        }

        $this->redirect(route('admin.districts'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.districts.form')
            ->layout('layouts.admin', ['title' => $this->districtId ? 'Edit District' : 'New District']);
    }
}
