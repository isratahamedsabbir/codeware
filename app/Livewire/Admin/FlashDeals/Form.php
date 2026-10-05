<?php

namespace App\Livewire\Admin\FlashDeals;

use App\Models\FlashDeal;
use App\Models\Product;
use App\Support\AdminActivity;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $flashDealId = null;

    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('required|in:percentage,fixed')]
    public string $type = 'percentage';

    #[Validate('required|numeric|min:0')]
    public string $value = '';

    #[Validate('required|date')]
    public string $starts_at = '';

    #[Validate('required|date')]
    public string $ends_at = '';

    /** @var array<int, int> */
    public array $product_ids = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $deal = FlashDeal::with('products')->findOrFail($id);
            $this->flashDealId = $id;
            $this->name = $deal->name;
            $this->type = $deal->type;
            $this->value = (string) $deal->value;
            $this->starts_at = $deal->starts_at->format('Y-m-d\TH:i');
            $this->ends_at = $deal->ends_at->format('Y-m-d\TH:i');
            $this->product_ids = $deal->products->pluck('id')->all();
        } else {
            $this->starts_at = now()->format('Y-m-d\TH:i');
            $this->ends_at = now()->addDay()->format('Y-m-d\TH:i');
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

        $rules['ends_at'] = 'required|date|after:starts_at';
        $rules['product_ids'] = 'required|array|min:1';
        $rules['product_ids.*'] = 'exists:products,id';

        $this->validate($rules, [
            'ends_at.after' => 'The end must be after the start.',
            'product_ids.required' => 'Pick at least one product for the deal.',
            'product_ids.min' => 'Pick at least one product for the deal.',
        ]);

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
        ];

        $creating = $this->flashDealId === null;

        if ($this->flashDealId) {
            $deal = FlashDeal::findOrFail($this->flashDealId);
            $deal->update($data);
            $this->dispatch('notify', message: 'Flash deal updated successfully');
        } else {
            // Like a Discount, a new deal stays inactive until switched on from the
            // list — see Index::toggleStatus().
            $data['status'] = 'inactive';
            $deal = FlashDeal::create($data);
            $this->dispatch('notify', message: 'Flash deal created successfully');
        }

        $deal->products()->sync($this->product_ids);

        AdminActivity::log($creating ? 'created' : 'updated', "Flash deal: {$data['name']}");

        $this->redirect(route('admin.flash-deals'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.flash-deals.form')
            ->layout('layouts.admin', ['title' => $this->flashDealId ? 'Edit Flash Deal' : 'New Flash Deal']);
    }
}
