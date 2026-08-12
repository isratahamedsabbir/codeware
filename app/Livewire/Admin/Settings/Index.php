<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\Theme;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Index extends Component
{
    public array $settings = [];

    public string $activeTab = 'general';

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $rows = Setting::all();

        foreach ($rows as $setting) {
            $this->settings[$setting->key] = $setting->value ?? '';
        }
    }

    public function save(): void
    {
        foreach ($this->settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            Cache::forget("setting:{$key}");
        }

        if (array_key_exists('theme_mode', $this->settings) || array_key_exists('theme_accent', $this->settings)) {
            $this->dispatch('admin-theme-changed', mode: $this->settings['theme_mode'] ?? Theme::mode(), accent: $this->settings['theme_accent'] ?? Theme::accent());
        }

        session()->flash('success', 'Settings saved.');
    }

    public function getEditorUrl(string $type): string
    {
        $token = auth()->user()->createToken('builder')->plainTextToken;
        $baseUrl = config('cms.editor_base_url', 'http://localhost:3000');

        return "{$baseUrl}/editor?mode=layout&type={$type}&token={$token}";
    }

    public function render()
    {
        return view('livewire.admin.settings.index', [
            'groupedSettings' => Setting::whereNotIn('group', ['layout', 'payments', 'seo', 'theme', 'colors', 'currency'])
                ->get()
                ->groupBy('group'),
            'colorSettings' => Setting::where('group', 'colors')->get(),
            'currencySettings' => Setting::where('group', 'currency')->get(),
        ])->layout('layouts.admin', ['title' => 'Settings']);
    }
}
