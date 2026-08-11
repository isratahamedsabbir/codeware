<?php

namespace App\Livewire\Admin\Dealers;

use App\Models\Dealer;
use App\Models\District;
use App\Models\ProductCategory;
use App\Models\Upazila;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $dealerId = null;

    #[Validate('required|string|max:255')]
    public string $name_en = '';

    #[Validate('nullable|string|max:255')]
    public string $name_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    #[Validate('nullable|string')]
    public string $address_en = '';

    #[Validate('nullable|string')]
    public string $address_bn = '';

    #[Validate('required|string|max:100')]
    public string $district = '';

    #[Validate('nullable|string|max:100')]
    public string $upazila = '';

    #[Validate('required|string|max:50')]
    public string $phone = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|numeric|between:-90,90')]
    public ?string $latitude = null;

    #[Validate('nullable|numeric|between:-180,180')]
    public ?string $longitude = null;

    #[Validate('in:active,inactive')]
    public string $status = 'active';

    public int $sort_order = 0;

    public array $selectedCategories = [];

    public ?string $selectedDistrictId = null;

    public ?string $photo = null;

    public string $photoPickerId = '';

    public function mount(?int $id = null): void
    {
        $this->photoPickerId = 'dealer-photo-picker-' . Str::uuid()->toString();

        if ($id) {
            $dealer = Dealer::findOrFail($id);
            $this->dealerId           = $id;
            $this->name_en            = $dealer->getTranslation('name', 'en', false) ?? '';
            $this->name_bn            = $dealer->getTranslation('name', 'bn', false) ?? '';
            $this->slug               = $dealer->slug;
            $this->address_en         = $dealer->getTranslation('address', 'en', false) ?? '';
            $this->address_bn         = $dealer->getTranslation('address', 'bn', false) ?? '';
            $this->district           = $dealer->district;
            $this->upazila            = $dealer->upazila ?? '';
            $this->phone              = $dealer->phone;
            $this->email              = $dealer->email ?? '';
            $this->photo              = $dealer->photo ?? null;
            $this->latitude           = $dealer->latitude !== null ? (string) $dealer->latitude : null;
            $this->longitude          = $dealer->longitude !== null ? (string) $dealer->longitude : null;
            $this->status             = $dealer->status;
            $this->sort_order         = $dealer->sort_order;
            $this->selectedCategories = $dealer->categories->pluck('id')->toArray();
            $this->selectedDistrictId = District::where('name->en', $dealer->district)
                ->orWhere('name->bn', $dealer->district)
                ->value('id') ?: null;
            if ($this->selectedDistrictId) {
                $this->selectedDistrictId = (string) $this->selectedDistrictId;
            }
        }
    }

    public function updatedSelectedDistrictId(): void
    {
        $this->upazila = '';
    }

    #[Computed]
    public function productCategories()
    {
        return ProductCategory::orderBy('sort_order')->get();
    }

    #[Computed]
    public function districts()
    {
        return District::orderBy('sort_order')->get();
    }

    #[Computed]
    public function upazilas()
    {
        if (!$this->selectedDistrictId) {
            return collect();
        }
        return Upazila::where('district_id', $this->selectedDistrictId)
            ->orderBy('sort_order')
            ->get();
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->dealerId
            ? 'required|string|max:255|unique:dealers,slug,' . $this->dealerId
            : 'required|string|max:255|unique:dealers,slug';

        if ($this->selectedDistrictId) {
            $districtModel = District::find($this->selectedDistrictId);
            $this->district = $districtModel?->getTranslation('name', 'en', false) ?? $this->district;
        }

        $this->validate($rules);

        $data = [
            'name'      => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug'      => $this->slug,
            'address'   => array_filter(['en' => $this->address_en, 'bn' => $this->address_bn]) ?: null,
            'district'  => $this->district,
            'upazila'   => $this->upazila ?: null,
            'phone'     => $this->phone,
            'email'     => $this->email ?: null,
            'photo'     => $this->photo ?: null,
            'latitude'  => $this->latitude !== null && $this->latitude !== '' ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null && $this->longitude !== '' ? (float) $this->longitude : null,
            'status'    => $this->status,
            'sort_order'=> $this->sort_order,
        ];

        if ($this->dealerId) {
            $dealer = Dealer::findOrFail($this->dealerId);
            $dealer->update($data);
            $this->dispatch('notify', message: 'Dealer updated successfully');
        } else {
            $dealer = Dealer::create($data);
            $this->dispatch('notify', message: 'Dealer created successfully');
        }

        $dealer->categories()->sync($this->selectedCategories);

        $this->redirect(route('admin.dealers'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.dealers.form')
            ->layout('layouts.admin', ['title' => $this->dealerId ? 'Edit Dealer' : 'New Dealer']);
    }
}
