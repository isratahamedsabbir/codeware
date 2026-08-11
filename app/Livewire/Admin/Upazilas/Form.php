<?php

namespace App\Livewire\Admin\Upazilas;

use App\Models\District;
use App\Models\Upazila;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $upazilaId = null;

    #[Validate('required|exists:districts,id')]
    public string $district_id = '';

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
            $upazila = Upazila::findOrFail($id);
            $this->upazilaId   = $id;
            $this->district_id = (string) $upazila->district_id;
            $this->name_en     = $upazila->getTranslation('name', 'en', false) ?? '';
            $this->name_bn     = $upazila->getTranslation('name', 'bn', false) ?? '';
            $this->slug        = $upazila->slug;
            $this->sort_order  = $upazila->sort_order;
            $this->status      = $upazila->status;
        }
    }

    #[Computed]
    public function districts()
    {
        return District::orderBy('sort_order')->get();
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->upazilaId
            ? 'required|string|max:255|unique:upazilas,slug,' . $this->upazilaId
            : 'required|string|max:255|unique:upazilas,slug';

        $this->validate($rules);

        $data = [
            'district_id' => $this->district_id,
            'name'        => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug'        => $this->slug,
            'sort_order'  => $this->sort_order,
            'status'      => $this->status,
        ];

        if ($this->upazilaId) {
            Upazila::findOrFail($this->upazilaId)->update($data);
            $this->dispatch('notify', message: 'Upazila updated successfully');
        } else {
            Upazila::create($data);
            $this->dispatch('notify', message: 'Upazila created successfully');
        }

        $this->redirect(route('admin.upazilas'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.upazilas.form')
            ->layout('layouts.admin', ['title' => $this->upazilaId ? 'Edit Upazila' : 'New Upazila']);
    }
}
