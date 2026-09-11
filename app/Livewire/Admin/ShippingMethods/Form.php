<?php

namespace App\Livewire\Admin\ShippingMethods;

use App\Models\ShippingMethod;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $shippingMethodId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|numeric|min:0')]
    public string $cost = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $method = ShippingMethod::findOrFail($id);
            $this->shippingMethodId = $id;
            $this->name = $method->name;
            $this->cost = (string) $method->cost;
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->shippingMethodId
            ? 'required|string|max:255|unique:shipping_methods,name,'.$this->shippingMethodId
            : 'required|string|max:255|unique:shipping_methods,name';

        $this->validate($rules);

        $data = ['name' => $this->name, 'cost' => $this->cost];

        $creating = $this->shippingMethodId === null;

        if ($this->shippingMethodId) {
            ShippingMethod::findOrFail($this->shippingMethodId)->update($data);
            $this->dispatch('notify', message: 'Shipping method updated successfully');
        } else {
            // New shipping methods stay inactive until switched on from the list —
            // status is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            ShippingMethod::create($data);
            $this->dispatch('notify', message: 'Shipping method created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Shipping Method: {$this->name}",
        );

        $this->redirect(route('admin.shipping-methods'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.shipping-methods.form')
            ->layout('layouts.admin', ['title' => $this->shippingMethodId ? 'Edit Shipping Method' : 'New Shipping Method']);
    }
}
