<?php

namespace App\Livewire\Admin\ProductBrands;

use App\Concerns\HasTranslatableFields;
use App\Models\ProductBrand;
use App\Support\AdminActivity;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $brandId = null;

    public array $name = [];

    public string $logo = '';

    /**
     * Which pool the brand belongs to — same post/product split as Tag. Only
     * product brands appear in the Product form's brand dropdown today; post
     * brands are tracked for parity and show up in the Brand list/filter.
     */
    #[Validate('required|in:post_brand,product_brand')]
    public string $type = ProductBrand::TYPE_PRODUCT;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $brand = ProductBrand::findOrFail($id);
            $this->brandId = $id;
            $this->hydrateTranslatable($brand, ['name']);
            $this->logo = $brand->logo ?? '';
            $this->type = $brand->type;
        }
    }

    public function save(): void
    {
        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]));

        // Uniqueness is enforced against brand rows only (post_brand/product_brand);
        // the column is the JSON path, so the primary locale's value is what's
        // compared — a tag or category sharing the string is fine.
        $rules['name.'.$this->primaryLocale][] = Rule::unique('categories', 'name->'.$this->primaryLocale)
            ->where(fn ($q) => $q->whereIn('type', ProductBrand::TYPES)->orWhereNull('type'))
            ->ignore($this->brandId);

        $this->validate($rules);

        $data = [
            'name' => $this->translatablePayload('name'),
            'logo' => $this->logo ?: null,
            'type' => $this->type,
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
            "Product Brand: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.product-brands'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.product-brands.form')
            ->layout('layouts.admin', ['title' => $this->brandId ? 'Edit Brand' : 'New Brand']);
    }
}
