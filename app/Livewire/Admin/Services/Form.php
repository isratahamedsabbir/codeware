<?php

namespace App\Livewire\Admin\Services;

use App\Concerns\HasTranslatableFields;
use App\Models\Service;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $serviceId = null;

    public array $name = [];

    public array $description = [];

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    #[Validate('required|numeric|min:0|max:99999999.99')]
    public string $price = '';

    public string $featuredImage = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $service = Service::findOrFail($id);
            $this->serviceId = $id;
            $this->hydrateTranslatable($service, ['name', 'description']);
            $this->slug = $service->slug;
            $this->price = (string) $service->price;
            $this->featuredImage = $service->featured_image ?? '';
        }
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->primaryValue('name')) {
            $this->slug = Str::slug($this->primaryValue('name'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]));
        $rules['slug'] = $this->serviceId
            ? 'required|string|max:255|unique:services,slug,'.$this->serviceId
            : 'required|string|max:255|unique:services,slug';

        $this->validate($rules);

        $data = [
            'name' => $this->translatablePayload('name'),
            'description' => $this->translatablePayload('description') ?: null,
            'slug' => $this->slug,
            'price' => $this->price,
            'featured_image' => $this->featuredImage ?: null,
        ];

        if ($this->serviceId) {
            Service::findOrFail($this->serviceId)->update($data);
            $this->dispatch('notify', message: 'Service updated successfully');
        } else {
            // New services stay inactive until switched on from the list —
            // status is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            Service::create($data);
            $this->dispatch('notify', message: 'Service created successfully');
        }

        AdminActivity::log(
            $this->serviceId ? 'updated' : 'created',
            "Service: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.services'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.services.form')
            ->layout('layouts.admin', ['title' => $this->serviceId ? 'Edit Service' : 'New Service']);
    }
}
