<?php

namespace App\Livewire\Admin\Departments;

use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $departmentId = null;

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
            $department = Department::findOrFail($id);
            $this->departmentId = $id;
            $this->name_en      = $department->getTranslation('name', 'en', false) ?? '';
            $this->name_bn      = $department->getTranslation('name', 'bn', false) ?? '';
            $this->slug         = $department->slug;
            $this->sort_order   = $department->sort_order;
            $this->status       = $department->status;
        }
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->departmentId
            ? 'required|string|max:255|unique:departments,slug,' . $this->departmentId
            : 'required|string|max:255|unique:departments,slug';

        $this->validate($rules);

        $data = [
            'name'       => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug'       => $this->slug,
            'sort_order' => $this->sort_order,
            'status'     => $this->status,
        ];

        if ($this->departmentId) {
            Department::findOrFail($this->departmentId)->update($data);
            $this->dispatch('notify', message: 'Department updated successfully');
        } else {
            Department::create($data);
            $this->dispatch('notify', message: 'Department created successfully');
        }

        $this->redirect(route('admin.departments'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.departments.form')
            ->layout('layouts.admin', ['title' => $this->departmentId ? 'Edit Department' : 'New Department']);
    }
}
