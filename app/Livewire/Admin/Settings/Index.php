<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\EnvFile;
use App\Support\Features;
use App\Support\Theme;
use Illuminate\Support\Facades\Artisan;
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
            // Boolean settings are stored as the string "0"/"1" (no cast on the
            // Setting model). Left as a string, a checkbox bound to it renders
            // checked no matter what — JS truthiness treats "0" as true, unlike
            // PHP. Cast to a real boolean so the checkbox reflects the stored value.
            $this->settings[$setting->key] = $setting->type === 'boolean'
                ? (bool) $setting->value
                : ($setting->value ?? '');
        }

        // Features default to enabled — only rows explicitly saved as off exist in
        // the settings table, so a feature with no row yet still shows checked here.
        // These aren't seeded with type=boolean (they're created on the fly by
        // save()), so cast explicitly regardless of the stored type.
        foreach (Features::ALL as $key => $label) {
            $settingKey = Features::settingKey($key);

            $this->settings[$settingKey] = array_key_exists($settingKey, $this->settings)
                ? (bool) $this->settings[$settingKey]
                : true;
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
                'APP_ENV' => ['label' => 'Environment', 'type' => 'select', 'options' => ['local', 'staging', 'production', 'testing', 'developer']],
                'APP_DEBUG' => ['label' => 'Debug Mode', 'type' => 'boolean'],
                'APP_URL' => ['label' => 'App URL', 'type' => 'text'],
                'FRONTEND_URL' => ['label' => 'Frontend URL', 'type' => 'text'],
            ],
        ];
    }

    public function confirmSaveEnv(): void
    {
        $rules = [
            'env.APP_NAME' => 'required|string',
            'env.APP_ENV' => 'required|in:local,staging,production,testing,developer',
            'env.APP_DEBUG' => 'required|in:true,false',
            'env.APP_URL' => 'required|url',
            'env.FRONTEND_URL' => 'nullable|url',
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
            Setting::set($key, $value);
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
        // Section order within the General tab — not the DB row order, which isn't
        // guaranteed without an ORDER BY. General and Images render side by side (see
        // the view), so their relative order here doesn't matter; anything not listed
        // falls to the end in whatever order it comes.
        $groupOrder = ['general' => 0, 'images' => 1, 'localization' => 2, 'frontend' => 3];

        return view('livewire.admin.settings.index', [
            'groupedSettings' => Setting::whereNotIn('group', ['layout', 'payments', 'seo', 'theme', 'colors', 'currency', 'social'])
                ->get()
                ->groupBy('group')
                ->sortBy(fn ($items, $group) => $groupOrder[$group] ?? count($groupOrder)),
            'colorSettings' => Setting::where('group', 'colors')->get(),
            'currencySettings' => Setting::where('group', 'currency')->get(),
        ])->layout('layouts.admin', ['title' => 'Settings']);
    }
}
