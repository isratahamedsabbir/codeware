<?php

namespace App\Livewire\Admin\Services;

use App\Concerns\HasTranslatableFields;
use App\Concerns\StaysOnForm;
use App\Models\Service;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;
    use StaysOnForm;

    public ?int $serviceId = null;

    public array $name = [];

    public array $description = [];

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    /**
     * The last auto-generated slug value, so we know whether the admin has
     * manually diverged from it — see updated().
     */
    public string $autoSlug = '';

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

    /**
     * Live slug-as-you-type — regenerates from the primary locale's name only
     * while the slug is still empty or still what we last generated (i.e. the
     * admin hasn't typed their own). An existing record's loaded slug never
     * matches autoSlug, so editing its name leaves the slug alone. Livewire's
     * updated{Field}() hooks don't fire for array keys like "name.en", hence
     * the catch-all.
     */
    public function updated(string $name, mixed $value): void
    {
        if (! $this->isPrimaryLocaleUpdate($name, 'name')) {
            return;
        }

        if ($this->slug === '' || $this->slug === $this->autoSlug) {
            $this->autoSlug = Slug::make((string) $value);
            $this->slug = $this->autoSlug;
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

        $creating = $this->serviceId === null;

        if ($this->serviceId) {
            Service::findOrFail($this->serviceId)->update($data);
            $this->dispatch('notify', message: 'Service updated successfully');
        } else {
            // New services stay inactive until switched on from the list —
            // status is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $this->serviceId = Service::create($data)->id;
            $this->dispatch('notify', message: 'Service created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Service: {$this->primaryValue('name')}",
        );

        $this->stayOnForm($creating, 'admin.services.edit', $this->serviceId);
    }

    public function render()
    {
        return view('livewire.admin.services.form')
            ->layout('layouts.admin', ['title' => $this->serviceId ? 'Edit Service' : 'New Service']);
    }
}
