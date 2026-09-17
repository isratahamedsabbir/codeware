<?php

namespace App\Livewire\Admin\Vouchers;

use App\Concerns\HasTranslatableFields;
use App\Models\Voucher;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $voucherId = null;

    public array $name = [];

    public array $description = [];

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    #[Validate('required|numeric|min:0|max:99999999.99')]
    public string $price = '';

    #[Validate('required|numeric|min:0|max:99999999.99')]
    public string $value = '';

    #[Validate('required|string|size:3')]
    public string $currency = 'BDT';

    #[Validate('nullable|integer|min:1|max:3650')]
    public string $valid_days = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $voucher = Voucher::findOrFail($id);
            $this->voucherId = $id;
            $this->hydrateTranslatable($voucher, ['name', 'description']);
            $this->slug = $voucher->slug;
            $this->price = (string) $voucher->price;
            $this->value = (string) $voucher->value;
            $this->currency = $voucher->currency;
            $this->valid_days = $voucher->valid_days !== null ? (string) $voucher->valid_days : '';
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
        $rules['slug'] = $this->voucherId
            ? 'required|string|max:255|unique:vouchers,slug,'.$this->voucherId
            : 'required|string|max:255|unique:vouchers,slug';

        $this->validate($rules);

        $data = [
            'name' => $this->translatablePayload('name'),
            'description' => $this->translatablePayload('description') ?: null,
            'slug' => $this->slug,
            'price' => $this->price,
            'value' => $this->value,
            'currency' => strtoupper($this->currency),
            'valid_days' => $this->valid_days !== '' ? (int) $this->valid_days : null,
        ];

        if ($this->voucherId) {
            Voucher::findOrFail($this->voucherId)->update($data);
            $this->dispatch('notify', message: 'Voucher updated successfully');
        } else {
            // New vouchers stay inactive until switched on from the list —
            // status is not editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            Voucher::create($data);
            $this->dispatch('notify', message: 'Voucher created successfully');
        }

        AdminActivity::log(
            $this->voucherId ? 'updated' : 'created',
            "Voucher: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.vouchers'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.vouchers.form')
            ->layout('layouts.admin', ['title' => $this->voucherId ? 'Edit Voucher' : 'New Voucher']);
    }
}
