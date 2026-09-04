<?php

namespace App\Livewire\Admin\Features;

use App\Models\Feature;
use App\Support\AdminActivity;
use App\Support\Features;
use Livewire\Component;

class Index extends Component
{
    /** @var array<string, bool> */
    public array $features = [];

    public function mount(): void
    {
        abort_unless(app()->environment('developer'), 404);

        $this->loadFeatures();
    }

    /**
     * Features default to enabled — a key with no row yet in the `features`
     * table (a feature added to Features::ALL after the table was last seeded)
     * still shows checked here.
     */
    protected function loadFeatures(): void
    {
        $enabled = Feature::query()->pluck('is_enabled', 'key');

        foreach (Features::ALL as $key => $label) {
            $this->features[$key] = (bool) ($enabled[$key] ?? true);
        }
    }

    public function save(): void
    {
        foreach ($this->features as $key => $enabled) {
            if (! array_key_exists($key, Features::ALL)) {
                continue;
            }

            Feature::updateOrCreate(['key' => $key], ['label' => Features::ALL[$key], 'is_enabled' => $enabled]);
        }

        AdminActivity::log('updated', 'Feature toggles updated');

        $this->dispatch('notify', message: 'Features saved.');
    }

    public function render()
    {
        return view('livewire.admin.features.index')->layout('layouts.admin', ['title' => 'Features']);
    }
}
