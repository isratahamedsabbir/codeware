<?php

namespace App\Livewire\Admin\ProductVendors;

use App\Models\ProductVendor;
use App\Models\User;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $vendorId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public string $logo = '';

    #[Validate('nullable|string')]
    public string $address = '';

    /**
     * User ids allowed to log into the vendor portal for this vendor — a user
     * can be assigned to more than one vendor, see ProductVendor::users().
     *
     * @var array<int, int>
     */
    public array $user_ids = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $vendor = ProductVendor::findOrFail($id);
            $this->vendorId = $id;
            $this->name = $vendor->name;
            $this->logo = $vendor->logo ?? '';
            $this->address = $vendor->address ?? '';
            $this->user_ids = $vendor->users->pluck('id')->all();
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->vendorId
            ? 'required|string|max:255|unique:product_vendors,name,'.$this->vendorId
            : 'required|string|max:255|unique:product_vendors,name';
        $rules['user_ids'] = 'array';
        $rules['user_ids.*'] = 'integer|exists:users,id';

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'logo' => $this->logo ?: null,
            'address' => $this->address ?: null,
        ];

        $creating = $this->vendorId === null;

        if ($this->vendorId) {
            // Status is toggled from the index list (see Index::toggleStatus()),
            // not this form — omit it here so saving never resets an already
            // deactivated vendor back to active.
            $vendor = ProductVendor::findOrFail($this->vendorId);
            $vendor->update($data);
            $this->dispatch('notify', message: 'Vendor updated successfully');
        } else {
            $data['status'] = 'active';
            $vendor = ProductVendor::create($data);
            $this->vendorId = $vendor->id;
            $this->dispatch('notify', message: 'Vendor created successfully');
        }

        $vendor->users()->sync($this->user_ids);

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Product Vendor: {$this->name}",
        );

        $this->redirect(route('admin.product-vendors'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.product-vendors.form', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ])->layout('layouts.admin', ['title' => $this->vendorId ? 'Edit Vendor' : 'New Vendor']);
    }
}
