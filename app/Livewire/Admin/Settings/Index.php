<?php

namespace App\Livewire\Admin\Settings;

use App\Concerns\PasswordValidationRules;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Index extends Component
{
    use PasswordValidationRules;

    public array $settings = [];

    public string $activeTab = 'general';

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

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
            \Illuminate\Support\Facades\Cache::forget("setting:{$key}");
        }

        session()->flash('success', 'Settings saved.');
    }

    public function getEditorUrl(string $type): string
    {
        $token = auth()->user()->createToken('builder')->plainTextToken;
        $baseUrl = config('cms.editor_base_url', 'http://localhost:3000');

        return "{$baseUrl}/editor?mode=layout&type={$type}&token={$token}";
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        session()->flash('success', 'Password updated.');
    }

    public function render()
    {
        return view('livewire.admin.settings.index', [
            'groupedSettings' => Setting::where('group', '!=', 'layout')
                ->get()
                ->groupBy('group'),
        ])->layout('layouts.admin', ['title' => 'Settings']);
    }
}
