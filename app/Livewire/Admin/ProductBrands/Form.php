<?php

namespace App\Livewire\Admin\ProductBrands;

use App\Models\ProductBrand;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $brandId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public string $logo = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $brand = ProductBrand::findOrFail($id);
            $this->brandId = $id;
            $this->name = $brand->name;
            $this->logo = $brand->logo ?? '';
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->brandId
            ? 'required|string|max:255|unique:product_brands,name,'.$this->brandId
            : 'required|string|max:255|unique:product_brands,name';

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'logo' => $this->logo ?: null,
        ];

        $creating = $this->brandId === null;

        if ($this->brandId) {
            // Status is toggled from the index list (see Index::toggleStatus()),
            // not this form — omit it here so saving never resets an already
            // deactivated brand back to active.
            ProductBrand::findOrFail($this->brandId)->update($data);
            $this->dispatch('notify', message: 'Brand updated successfully');
        } else {
            $data['status'] = 'active';
            ProductBrand::create($data);
            $this->dispatch('notify', message: 'Brand created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Product Brand: {$this->name}",
        );

        $this->redirect(route('admin.product-brands'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.product-brands.form')
            ->layout('layouts.admin', ['title' => $this->brandId ? 'Edit Brand' : 'New Brand']);
    }
}
