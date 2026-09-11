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

    /**
     * Predefined values an admin can pick from when adding this attribute to a
     * product's Variations (e.g. Size → Small/Medium/Large) — the product form
     * only lets them select one of these, never type a new one.
     *
     * @var array<int, string>
     */
    public array $values = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $attribute = ProductAttribute::findOrFail($id);
            $this->attributeId = $id;
            $this->name = $attribute->name;
            $this->values = $attribute->values ?? [];
        }
    }

    public function addValue(): void
    {
        $this->values[] = '';
    }

    public function removeValue(int $index): void
    {
        unset($this->values[$index]);
        $this->values = array_values($this->values);
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->attributeId
            ? 'required|string|max:255|unique:product_attributes,name,'.$this->attributeId
            : 'required|string|max:255|unique:product_attributes,name';
        $rules['values.*'] = 'nullable|string|max:255';

        $this->validate($rules);

        $cleanedValues = collect($this->values)
            ->map(fn ($value) => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $data = ['name' => $this->name, 'values' => $cleanedValues];

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
