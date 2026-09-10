<?php

namespace App\Livewire\Admin\ProductAttributes;

use App\Models\ProductAttribute;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $attributeId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|integer|min:0')]
    public string $sort_order = '0';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $attribute = ProductAttribute::findOrFail($id);
            $this->attributeId = $id;
            $this->name = $attribute->name;
            $this->sort_order = (string) $attribute->sort_order;
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->attributeId
            ? 'required|string|max:255|unique:product_attributes,name,'.$this->attributeId
            : 'required|string|max:255|unique:product_attributes,name';

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'sort_order' => $this->sort_order !== '' ? $this->sort_order : 0,
        ];

        $creating = $this->attributeId === null;

        if ($this->attributeId) {
            ProductAttribute::findOrFail($this->attributeId)->update($data);
            $this->dispatch('notify', message: 'Attribute updated successfully');
        } else {
            ProductAttribute::create($data);
            $this->dispatch('notify', message: 'Attribute created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Product Attribute: {$this->name}",
        );

        $this->redirect(route('admin.product-attributes'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.product-attributes.form')
            ->layout('layouts.admin', ['title' => $this->attributeId ? 'Edit Attribute' : 'New Attribute']);
    }
}
