<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\EnvFile;
use App\Support\Theme;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Index extends Component
{
    public array $settings = [];

    public array $env = [];

    public string $activeTab = 'general';

    public function mount(): void
    {
        $this->loadSettings();
        $this->loadEnv();
    }

    protected function loadSettings(): void
    {
        $rows = Setting::all();

        foreach ($rows as $setting) {
            $this->settings[$setting->key] = $setting->value ?? '';
        }
    }

    protected function loadEnv(): void
    {
        $current = EnvFile::all();

        foreach ($this->envFields() as $fields) {
            foreach (array_keys($fields) as $key) {
                $this->env[$key] = $current[$key] ?? '';
            }
        }
    }

    /**
     * Editable .env keys, grouped for the Env tab. Deliberately excludes APP_KEY and
     * anything else whose value would be unsafe to expose or that shouldn't be edited
     * through a web form (encryption key, session/cache drivers, etc.).
     *
     * @return array<string, array<string, array{label: string, type: string, options?: array<int, string>}>>
     */
    public function envFields(): array
    {
        return [
            'App' => [
                'APP_NAME' => ['label' => 'App Name', 'type' => 'text'],
                'APP_ENV' => ['label' => 'Environment', 'type' => 'select', 'options' => ['local', 'staging', 'production', 'testing']],
                'APP_URL' => ['label' => 'App URL', 'type' => 'text'],
                'APP_LOCALE' => ['label' => 'Locale', 'type' => 'text'],
            ],
            'Debug' => [
                'APP_DEBUG' => ['label' => 'Debug Mode', 'type' => 'boolean'],
                'LOG_CHANNEL' => ['label' => 'Log Channel', 'type' => 'text'],
                'LOG_LEVEL' => ['label' => 'Log Level', 'type' => 'select', 'options' => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency']],
            ],
            'Email' => [
                'MAIL_MAILER' => ['label' => 'Mailer', 'type' => 'text'],
                'MAIL_HOST' => ['label' => 'Host', 'type' => 'text'],
                'MAIL_PORT' => ['label' => 'Port', 'type' => 'text'],
                'MAIL_USERNAME' => ['label' => 'Username', 'type' => 'text'],
                'MAIL_PASSWORD' => ['label' => 'Password', 'type' => 'password'],
                'MAIL_FROM_ADDRESS' => ['label' => 'From Address', 'type' => 'text'],
                'MAIL_FROM_NAME' => ['label' => 'From Name', 'type' => 'text'],
            ],
        ];
    }

    public function confirmSaveEnv(): void
    {
        $rules = [
            'env.APP_URL' => 'required|url',
            'env.MAIL_PORT' => 'nullable|numeric',
            'env.APP_DEBUG' => 'required|in:true,false',
        ];

        $this->validate($rules);

        $this->dispatch('open-modal', name: 'env-save-confirm');
    }

    public function saveEnv(): void
    {
        try {
            EnvFile::set($this->env);
        } catch (\RuntimeException $e) {
            $this->dispatch('close-modal', name: 'env-save-confirm');
            session()->flash('error', 'Could not save environment settings: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Environment settings updated');

        $this->dispatch('close-modal', name: 'env-save-confirm');
        session()->flash('success', 'Environment settings saved. Configuration cache cleared.');
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
            'groupedSettings' => Setting::whereNotIn('group', ['layout', 'payments', 'seo', 'theme', 'colors', 'currency', 'social'])
                ->get()
                ->groupBy('group'),
            'colorSettings' => Setting::where('group', 'colors')->get(),
            'currencySettings' => Setting::where('group', 'currency')->get(),
        ])->layout('layouts.admin', ['title' => 'Settings']);
    }
}
