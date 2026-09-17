<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use App\Models\Product;
use App\Support\AdminActivity;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $discountId = null;

    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('required|in:percentage,fixed')]
    public string $type = 'percentage';

    #[Validate('required|numeric|min:0')]
    public string $value = '';

    #[Validate('nullable|date')]
    public string $starts_at = '';

    #[Validate('nullable|date')]
    public string $ends_at = '';

    /** @var array<int, int> */
    public array $product_ids = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $discount = Discount::with('products')->findOrFail($id);
            $this->discountId = $id;
            $this->name = $discount->name;
            $this->type = $discount->type;
            $this->value = (string) $discount->value;
            $this->starts_at = $discount->starts_at?->format('Y-m-d') ?? '';
            $this->ends_at = $discount->ends_at?->format('Y-m-d') ?? '';
            $this->product_ids = $discount->products->pluck('id')->all();
        }
    }

    #[Computed]
    public function products()
    {
        return Product::orderBy('id')->get();
    }

    public function save(): void
    {
        $rules = $this->getRules();

        if ($this->type === 'percentage') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }

        if ($this->ends_at !== '' && $this->starts_at !== '' && $this->ends_at < $this->starts_at) {
            $this->addError('ends_at', 'End date must be on or after the start date.');

            return;
        }

        $rules['product_ids'] = 'array';
        $rules['product_ids.*'] = 'exists:products,id';

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'starts_at' => $this->starts_at !== '' ? $this->starts_at : null,
            'ends_at' => $this->ends_at !== '' ? $this->ends_at : null,
        ];

        $creating = $this->discountId === null;

        if ($this->discountId) {
            $discount = Discount::findOrFail($this->discountId);
            $discount->update($data);
            $this->dispatch('notify', message: 'Discount updated successfully');
        } else {
            // New discounts stay inactive until switched on from the list — status is
            // no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $discount = Discount::create($data);
            $this->dispatch('notify', message: 'Discount created successfully');
        }

        $discount->products()->sync($this->product_ids);

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Discount: {$data['name']}",
        );

        $this->redirect(route('admin.discounts'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.discounts.form')
            ->layout('layouts.admin', ['title' => $this->discountId ? 'Edit Discount' : 'New Discount']);
    }
}
