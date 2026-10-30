<?php

namespace App\Livewire\Admin\Vouchers;

use App\Concerns\HasTranslatableFields;
use App\Concerns\StaysOnForm;
use App\Models\Voucher;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;
    use StaysOnForm;

    public ?int $voucherId = null;

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

        $creating = $this->voucherId === null;

        if ($this->voucherId) {
            Voucher::findOrFail($this->voucherId)->update($data);
            $this->dispatch('notify', message: 'Voucher updated successfully');
        } else {
            // New vouchers stay inactive until switched on from the list —
            // status is not editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $this->voucherId = Voucher::create($data)->id;
            $this->dispatch('notify', message: 'Voucher created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Voucher: {$this->primaryValue('name')}",
        );

        $this->stayOnForm($creating, 'admin.vouchers.edit', $this->voucherId);
    }

    public function render()
    {
        return view('livewire.admin.vouchers.form')
            ->layout('layouts.admin', ['title' => $this->voucherId ? 'Edit Voucher' : 'New Voucher']);
    }
}
