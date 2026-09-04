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

    /** @var array<int, string> */
    public array $canonicalUrls = [];

    /** @var array<int, array{key: string, type: string, value: string}> */
    public array $constants = [];

    public function mount(): void
    {
        $this->loadSettings();
        $this->loadEnv();
        $this->loadCanonicalUrls();
        $this->loadConstants();
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

    protected function loadCanonicalUrls(): void
    {
        $this->canonicalUrls = json_decode(Setting::get('seo_canonical_urls', '[]') ?: '[]', true) ?: [];

        if (empty($this->canonicalUrls)) {
            $this->canonicalUrls = [''];
        }
    }

    public function addCanonicalUrl(): void
    {
        $this->canonicalUrls[] = '';
    }

    public function removeCanonicalUrl(int $index): void
    {
        unset($this->canonicalUrls[$index]);
        $this->canonicalUrls = array_values($this->canonicalUrls);

        if (empty($this->canonicalUrls)) {
            $this->canonicalUrls = [''];
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
            'Mail' => [
                'MAIL_MAILER' => ['label' => 'Mailer', 'type' => 'select', 'options' => ['smtp', 'log', 'sendmail', 'ses', 'postmark', 'resend']],
                'MAIL_HOST' => ['label' => 'SMTP Host', 'type' => 'text'],
                'MAIL_PORT' => ['label' => 'SMTP Port', 'type' => 'text'],
                'MAIL_USERNAME' => ['label' => 'SMTP Username', 'type' => 'text'],
                'MAIL_PASSWORD' => ['label' => 'SMTP Password', 'type' => 'password'],
                'MAIL_SCHEME' => ['label' => 'Encryption', 'type' => 'select', 'options' => ['null', 'tls', 'smtps']],
                'MAIL_FROM_ADDRESS' => ['label' => 'From Address', 'type' => 'text'],
                'MAIL_FROM_NAME' => ['label' => 'From Name', 'type' => 'text'],
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
            'env.MAIL_MAILER' => 'required|in:smtp,log,sendmail,ses,postmark,resend',
            'env.MAIL_HOST' => 'nullable|string',
            'env.MAIL_PORT' => 'nullable|numeric',
            'env.MAIL_USERNAME' => 'nullable|string',
            'env.MAIL_PASSWORD' => 'nullable|string',
            'env.MAIL_SCHEME' => 'required|in:null,tls,smtps',
            'env.MAIL_FROM_ADDRESS' => 'required|email',
            'env.MAIL_FROM_NAME' => 'required|string',
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

    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^constants\.\d+\.key$/', $name)) {
            $sanitized = preg_replace('/[^A-Za-z0-9_]/', '', preg_replace('/\s+/', '_', trim($value)));

            if ($sanitized !== $value) {
                data_set($this, $name, $sanitized);
            }
        }
    }

    public function applyThemePreset(string $name): void
    {
        $preset = collect(Theme::PRESETS)->firstWhere('name', $name);

        if (! $preset) {
            return;
        }

        $this->settings['theme_mode'] = $preset['mode'];
        $this->settings['theme_accent'] = $preset['accent'];
        $this->settings['theme_name'] = $preset['name'];
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

        $urls = array_values(array_filter($this->canonicalUrls, fn ($url) => trim($url) !== ''));
        Setting::set('seo_canonical_urls', json_encode($urls));

        $constants = collect($this->constants)->filter(fn ($pair) => filled($pair['key'] ?? null))->values()->all();
        Setting::set('constants', json_encode($constants));

        if (array_key_exists('theme_mode', $this->settings) || array_key_exists('theme_accent', $this->settings)) {
            $this->dispatch('admin-theme-changed', mode: $this->settings['theme_mode'] ?? Theme::mode(), accent: $this->settings['theme_accent'] ?? Theme::accent());
        }

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
            // 'frontend' (site_theme) lives under the Theme tab, alongside the admin
            // panel's own theme_mode/theme_accent/theme_name — not here.
            'groupedSettings' => Setting::whereNotIn('group', ['layout', 'payments', 'seo', 'theme', 'colors', 'currency', 'social', 'frontend'])
                ->get()
                ->groupBy('group')
                ->sortBy(fn ($items, $group) => $groupOrder[$group] ?? count($groupOrder)),
            'colorSettings' => Setting::where('group', 'colors')->get(),
            'currencySettings' => Setting::where('group', 'currency')->get(),
        ])->layout('layouts.admin', ['title' => 'Settings']);
    }
}
