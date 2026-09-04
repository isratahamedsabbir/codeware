<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\EnvFile;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class Index extends Component
{
    public array $settings = [];

    public array $env = [];

    public string $activeTab = 'general';

    /** @var array<int, array{key: string, type: string, value: string}> */
    public array $constants = [];

    public bool $maintenanceMode = false;

    public bool $debugMode = false;

    public function mount(): void
    {
        $this->loadSettings();
        $this->loadEnv();
        $this->loadConstants();
        $this->maintenanceMode = app()->isDownForMaintenance();
        $this->debugMode = (bool) config('app.debug');
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

    protected function loadConstants(): void
    {
        $this->constants = collect(json_decode(Setting::get('constants', '[]') ?: '[]', true) ?: [])
            ->map(fn (array $pair) => [...$pair, 'type' => in_array($pair['type'] ?? null, ['textarea', 'file'], true) ? $pair['type'] : 'textarea'])
            ->all();
    }

    public function addConstant(): void
    {
        $this->constants[] = ['key' => '', 'type' => 'textarea', 'value' => ''];
    }

    public function removeConstant(int $index): void
    {
        unset($this->constants[$index]);
        $this->constants = array_values($this->constants);
    }

    public function setConstantType(int $index, string $type): void
    {
        if (! array_key_exists($index, $this->constants) || ! in_array($type, ['textarea', 'file'], true)) {
            return;
        }

        $this->constants[$index]['type'] = $type;
    }

    /**
     * Editable .env keys, grouped for the Env tab. Deliberately excludes APP_KEY and
     * anything else whose value would be unsafe to expose or that shouldn't be edited
     * through a web form (encryption key, session/cache drivers, etc.). Mail credentials
     * live on the Email Templates page instead (see EmailTemplates\Index), next to the
     * "send a test email" action that actually exercises them.
     *
     * @return array<string, array<string, array{label: string, type: string, options?: array<int, string>}>>
     */
    public function envFields(): array
    {
        return [
            'App' => [
                'APP_NAME' => ['label' => 'App Name', 'type' => 'text'],
                'APP_ENV' => ['label' => 'Environment', 'type' => 'select', 'options' => ['local', 'staging', 'production', 'testing', 'developer']],
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
            $this->dispatch('notify', message: 'Could not save environment settings: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Environment settings updated');

        $this->dispatch('close-modal', name: 'env-save-confirm');
        $this->dispatch('notify', message: 'Environment settings saved. Configuration cache cleared.');
    }

    public function confirmEnableMaintenanceMode(): void
    {
        $this->dispatch('open-modal', name: 'maintenance-mode-confirm');
    }

    /**
     * Takes the public site offline — the admin panel and /login stay reachable
     * regardless (see bootstrap/app.php's preventRequestsDuringMaintenance
     * exceptions), so this can never lock the admin out of turning it back off.
     */
    public function enableMaintenanceMode(): void
    {
        Artisan::call('down');

        $this->maintenanceMode = true;

        AdminActivity::log('updated', 'Enabled maintenance mode');

        $this->dispatch('close-modal', name: 'maintenance-mode-confirm');
        $this->dispatch('notify', message: 'Maintenance mode enabled. The public site is now offline.');
    }

    public function disableMaintenanceMode(): void
    {
        Artisan::call('up');

        $this->maintenanceMode = false;

        AdminActivity::log('updated', 'Disabled maintenance mode');

        $this->dispatch('notify', message: 'Maintenance mode disabled. The site is back online.');
    }

    public function confirmEnableDebugMode(): void
    {
        $this->dispatch('open-modal', name: 'debug-mode-confirm');
    }

    /**
     * Debug mode has no artisan down/up equivalent — it's just APP_DEBUG in .env,
     * read through config('app.debug'). Writing it goes through EnvFile (same as
     * saveEnv()) followed by config:clear so the change takes effect immediately.
     */
    public function enableDebugMode(): void
    {
        if (! $this->writeDebugMode('true')) {
            return;
        }

        $this->debugMode = true;

        AdminActivity::log('updated', 'Enabled debug mode');

        $this->dispatch('close-modal', name: 'debug-mode-confirm');
        $this->dispatch('notify', message: 'Debug mode enabled. Errors will now show full stack traces to visitors.');
    }

    public function disableDebugMode(): void
    {
        if (! $this->writeDebugMode('false')) {
            return;
        }

        $this->debugMode = false;

        AdminActivity::log('updated', 'Disabled debug mode');

        $this->dispatch('notify', message: 'Debug mode disabled.');
    }

    protected function writeDebugMode(string $value): bool
    {
        try {
            EnvFile::set(['APP_DEBUG' => $value]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: 'Could not update debug mode: '.$e->getMessage());

            return false;
        }

        Artisan::call('config:clear');

        return true;
    }

    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^constants\.\d+\.key$/', $name)) {
            $sanitized = preg_replace('/[^A-Za-z0-9_]/', '', preg_replace('/\s+/', '_', trim($value)));

            if ($sanitized !== $value) {
                data_set($this, $name, $sanitized);
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'constants' => ['array', function (string $attribute, mixed $value, \Closure $fail) {
                $keys = collect($value)->pluck('key')->filter()->map(fn ($key) => strtolower(trim($key)));

                if ($keys->count() !== $keys->unique()->count()) {
                    $fail('Constant keys must be unique.');
                }
            }],
            'constants.*.key' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_]*$/'],
            'constants.*.type' => 'nullable|in:textarea,file',
            'constants.*.value' => 'nullable|string|max:1000',
        ]);

        foreach ($this->settings as $key => $value) {
            Setting::set($key, $value);
        }

        $constants = collect($this->constants)->filter(fn ($pair) => filled($pair['key'] ?? null))->values()->all();
        Setting::set('constants', json_encode($constants));

        $this->dispatch('notify', message: 'Settings saved.');
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
        // falls to the end in whatever order it comes. Pagination gets its own card
        // (via its own 'pagination' group) rather than sharing General's.
        $groupOrder = ['general' => 0, 'pagination' => 1, 'images' => 2, 'localization' => 3];

        return view('livewire.admin.settings.index', [
            // 'frontend' (site_theme) and 'colors' live under the Theme tab, not here.
            // 'other' is hand-rendered in its own tab (the Floating Button card) rather
            // than through this generic per-group loop.
            'groupedSettings' => Setting::whereNotIn('group', ['layout', 'seo', 'colors', 'currency', 'frontend', 'other'])
                ->get()
                ->groupBy('group')
                ->sortBy(fn ($items, $group) => $groupOrder[$group] ?? count($groupOrder)),
            'colorSettings' => Setting::where('group', 'colors')->get(),
            'currencySettings' => Setting::where('group', 'currency')->get(),
        ])->layout('layouts.admin', ['title' => 'Settings']);
    }
}
