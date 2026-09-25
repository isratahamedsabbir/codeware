<?php

namespace App\Livewire\Admin\Advertisements;

use App\Models\Advertisement;
use App\Support\AdminActivity;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $advertisementId = null;

    public ?string $code = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $image = '';

    #[Validate('nullable|url|max:2048')]
    public string $url = '';

    #[Validate('nullable|date')]
    public string $validFrom = '';

    #[Validate('nullable|date')]
    public string $validUntil = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $ad = Advertisement::findOrFail($id);
            $this->advertisementId = $id;
            $this->code = $ad->code;
            $this->name = $ad->name;
            $this->image = $ad->image ?? '';
            $this->url = $ad->url ?? '';
            $this->validFrom = $ad->valid_from?->format('Y-m-d') ?? '';
            $this->validUntil = $ad->valid_until?->format('Y-m-d') ?? '';
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();

        if ($this->validFrom !== '' && $this->validUntil !== '' && $this->validUntil < $this->validFrom) {
            $this->addError('validUntil', __('The end date must be on or after the start date.'));

            return;
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'image' => $this->image,
            'url' => $this->url ?: null,
            'valid_from' => $this->validFrom !== '' ? Carbon::parse($this->validFrom)->startOfDay() : null,
            'valid_until' => $this->validUntil !== '' ? Carbon::parse($this->validUntil)->endOfDay() : null,
        ];

        $creating = $this->advertisementId === null;

        if ($this->advertisementId) {
            Advertisement::findOrFail($this->advertisementId)->update($data);
            $this->dispatch('notify', message: 'Advertisement updated successfully');
        } else {
            Advertisement::create($data);
            $this->dispatch('notify', message: 'Advertisement created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Advertisement: {$this->name}",
        );

        $this->redirect(route('admin.advertisements'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.advertisements.form')
            ->layout('layouts.admin', ['title' => $this->advertisementId ? 'Edit Advertisement' : 'New Advertisement']);
    }
}
