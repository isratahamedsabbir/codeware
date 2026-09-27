<?php

namespace App\Livewire\Admin\ProductBrands;

use App\Concerns\HasTranslatableFields;
use App\Models\ProductBrand;
use App\Models\Type;
use App\Support\AdminActivity;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $brandId = null;

    public array $name = [];

    public string $logo = '';

    /**
     * Which pool the brand belongs to — the same post/product split as Tag.
     * Required, with no "shared (both)" option: a brand in neither pool was
     * never really a brand, so every row now picks exactly one (see the
     * add_type_id_to_categories migration). Only product brands appear in the
     * Product form's brand dropdown today; post brands are tracked for parity
     * and show up in the Brand list/filter.
     */
    #[Validate('required|integer|exists:types,id')]
    public ?int $typeId = null;

    public function mount(?int $id = null): void
    {
        if (! $id) {
            $this->typeId = $this->defaultTypeId();

            return;
        }

        $brand = ProductBrand::findOrFail($id);
        $this->brandId = $id;
        $this->hydrateTranslatable($brand, ['name']);
        $this->logo = $brand->logo ?? '';
        $this->typeId = $brand->type_id;
    }

    public function save(): void
    {
        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]));

        // Uniqueness is enforced against brand rows only (kind = brand); the
        // column is the JSON path, so the primary locale's value is what's
        // compared — a tag or category sharing the string is fine.
        $rules['name.'.$this->primaryLocale][] = Rule::unique('categories', 'name->'.$this->primaryLocale)
            ->where(fn ($q) => $q->where('kind', ProductBrand::KIND))
            ->ignore($this->brandId);

        $this->validate($rules);

        $data = [
            'name' => $this->translatablePayload('name'),
            'logo' => $this->logo ?: null,
            'type_id' => $this->typeId,
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

    /**
     * @return Collection<int, Type>
     */
    #[Computed]
    public function typeOptions()
    {
        return Type::selectOptions();
    }

    /**
     * Preselect the product pool, same default the form used before types were a
     * table — a brand added from the Brands list is overwhelmingly a product one.
     */
    private function defaultTypeId(): ?int
    {
        return Type::idFor(Type::PRODUCT) ?? Type::idFor(Type::POST);
    }

    public function render()
    {
        return view('livewire.admin.product-brands.form')
            ->layout('layouts.admin', ['title' => $this->brandId ? 'Edit Brand' : 'New Brand']);
    }
}
