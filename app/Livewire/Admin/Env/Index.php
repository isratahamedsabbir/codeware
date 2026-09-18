<?php

namespace App\Livewire\Admin\Env;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\EnvFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Index extends Component
{
    public array $env = [];

    /**
     * Only the two plain (DB-backed) Settings that live on this page — Tracking's
     * Google Pixel ID and the reCAPTCHA enable toggle — not every Setting row (that
     * generic loop stays on the main Settings page).
     *
     * @var array<string, mixed>
     */
    public array $settings = [];

    public bool $maintenanceMode = false;

    public bool $debugMode = false;

    public function mount(): void
    {
        $this->loadEnv();
        $this->loadSettings();
        $this->maintenanceMode = app()->isDownForMaintenance();
        $this->debugMode = (bool) config('app.debug');
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

    protected function loadSettings(): void
    {
        $this->settings['google_pixel_id'] = Setting::get('google_pixel_id', '') ?? '';
        $this->settings['recaptcha_enabled'] = (bool) Setting::get('recaptcha_enabled');
    }

    /**
     * Editable .env keys, grouped for this page. Deliberately excludes APP_KEY and
     * anything else whose value would be unsafe to expose or that shouldn't be edited
     * through a web form (encryption key, session driver, etc.). CACHE_STORE is the one
     * exception — it's exposed further down so a server without Redis can be switched
     * to 'database'/'file' without touching the file by hand (see saveEnv()'s Redis
     * reachability check). Mail credentials live on the Email Templates page instead
     * (see EmailTemplates\Index), next to the "send a test email" action that actually
     * exercises them.
     *
     * @return array<string, array<string, array{label: string, type: string, options?: array<int, string>, hint?: string}>>
     */
    public function envFields(): array
    {
        return [
            'App' => [
                'APP_NAME' => ['label' => 'App Name', 'type' => 'text'],
                'APP_ENV' => ['label' => 'Environment', 'type' => 'select', 'options' => ['local', 'staging', 'production', 'testing', 'developer']],
                'APP_URL' => ['label' => 'App URL', 'type' => 'text'],
                'FRONTEND_URL' => ['label' => 'Frontend URL', 'type' => 'text'],
                // Vendor Portal subdomain (see bootstrap/app.php) — e.g.
                // https://vendor.codeware.test locally, https://vendor.codeware.com
                // in production. Requires the corresponding DNS/hosts entry to
                // already exist; this only changes which host Laravel routes to
                // App\Livewire\Vendor\* and where vendor.* URLs point.
                'VENDOR_URL' => ['label' => 'Vendor Portal URL', 'type' => 'text'],
                // 'database'/'file' work on any server with no extra setup; 'redis' is
                // faster but only picked when the server actually has one — saveEnv()
                // refuses to save 'redis' here unless it can reach it first.
                'CACHE_STORE' => ['label' => 'Cache Store', 'type' => 'select', 'options' => ['database', 'file', 'redis'],
                    'hint' => "Pick database or file if this server doesn't have Redis installed — redis is only saved once it's confirmed reachable."],
            ],
            'Google Login' => [
                'GOOGLE_CLIENT_ID' => ['label' => 'Google Client ID', 'type' => 'text'],
                'GOOGLE_CLIENT_SECRET' => ['label' => 'Google Client Secret', 'type' => 'password'],
                'GOOGLE_REDIRECT_URI' => ['label' => 'Google Redirect URI', 'type' => 'text'],
            ],
            'Facebook Login' => [
                'FACEBOOK_CLIENT_ID' => ['label' => 'Facebook App ID', 'type' => 'text'],
                'FACEBOOK_CLIENT_SECRET' => ['label' => 'Facebook App Secret', 'type' => 'password'],
                'FACEBOOK_REDIRECT_URI' => ['label' => 'Facebook Redirect URI', 'type' => 'text'],
            ],
            'reCAPTCHA' => [
                'RECAPTCHA_SITE_KEY' => ['label' => 'Site Key', 'type' => 'text'],
                'RECAPTCHA_SECRET_KEY' => ['label' => 'Secret Key', 'type' => 'password'],
            ],
            'Google Maps' => [
                'GOOGLE_MAPS_API_KEY' => ['label' => 'API Key', 'type' => 'text'],
            ],
            'AWS S3' => [
                'AWS_ACCESS_KEY_ID' => ['label' => 'Access Key ID', 'type' => 'text'],
                'AWS_SECRET_ACCESS_KEY' => ['label' => 'Secret Access Key', 'type' => 'password'],
                'AWS_DEFAULT_REGION' => ['label' => 'Region', 'type' => 'text', 'hint' => 'e.g. us-east-1, ap-southeast-1.'],
                'AWS_BUCKET' => ['label' => 'Bucket', 'type' => 'text'],
                'AWS_USE_PATH_STYLE_ENDPOINT' => ['label' => 'Use Path-Style Endpoint', 'type' => 'boolean',
                    'hint' => 'Turn on only for S3-compatible services (e.g. MinIO, DigitalOcean Spaces) that need it — leave off for real AWS S3.'],
            ],
            'Firebase' => [
                'FIREBASE_CREDENTIALS_PATH' => ['label' => 'Service Account JSON Path', 'type' => 'text',
                    'hint' => 'Path relative to storage/app/private — upload the file there via File Manager first, then paste its path here.'],
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
            'env.VENDOR_URL' => 'nullable|url',
            'env.CACHE_STORE' => 'required|in:database,file,redis',
            'env.GOOGLE_CLIENT_ID' => 'nullable|string',
            'env.GOOGLE_CLIENT_SECRET' => 'nullable|string',
            // Not `url` — this intentionally holds a ${APP_URL}/... interpolation
            // (phpdotenv resolves it at runtime), which a strict URL check would reject.
            'env.GOOGLE_REDIRECT_URI' => 'nullable|string',
            'env.FACEBOOK_CLIENT_ID' => 'nullable|string',
            'env.FACEBOOK_CLIENT_SECRET' => 'nullable|string',
            'env.FACEBOOK_REDIRECT_URI' => 'nullable|string',
            'env.RECAPTCHA_SITE_KEY' => 'nullable|string',
            'env.RECAPTCHA_SECRET_KEY' => 'nullable|string',
            'env.GOOGLE_MAPS_API_KEY' => 'nullable|string',
            'env.AWS_ACCESS_KEY_ID' => 'nullable|string',
            'env.AWS_SECRET_ACCESS_KEY' => 'nullable|string',
            'env.AWS_DEFAULT_REGION' => 'nullable|string',
            'env.AWS_BUCKET' => 'nullable|string',
            'env.AWS_USE_PATH_STYLE_ENDPOINT' => 'nullable|in:true,false',
            'env.FIREBASE_CREDENTIALS_PATH' => 'nullable|string',
        ];

        $this->validate($rules);

        $this->dispatch('open-modal', name: 'env-save-confirm');
    }

    /**
     * Refuses to switch the cache store to Redis unless it can actually be
     * reached first — the whole point of exposing CACHE_STORE here is to let
     * a server without Redis fall back to 'database'/'file', so silently
     * saving 'redis' anyway would take every cache read/write down site-wide
     * the moment config:clear picks it up.
     */
    private function redisReachable(): bool
    {
        try {
            Redis::connection('cache')->ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Whether the currently-typed Firebase credentials path actually exists
     * on the private storage disk — checked live (see the field's
     * wire:model.live) so the warning in the UI reflects what's typed, not
     * just what was last saved.
     */
    public function firebaseCredentialsExist(): bool
    {
        $path = trim($this->env['FIREBASE_CREDENTIALS_PATH'] ?? '');

        return $path !== '' && Storage::disk('local')->exists($path);
    }

    public function saveEnv(): void
    {
        if (($this->env['CACHE_STORE'] ?? null) === 'redis' && ! $this->redisReachable()) {
            $this->dispatch('close-modal', name: 'env-save-confirm');
            $this->dispatch('notify', message: 'Could not save: Redis is not reachable from this server. Choose "database" or "file" instead, or fix the Redis connection first.');

            return;
        }

        try {
            EnvFile::set($this->env);
        } catch (\RuntimeException $e) {
            $this->dispatch('close-modal', name: 'env-save-confirm');
            $this->dispatch('notify', message: 'Could not save environment settings: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        // VENDOR_URL controls which host the Vendor Portal route group binds to
        // (see bootstrap/app.php) — if routes are ever cached (route:cache, as a
        // production deploy might run), that cache would keep serving the old
        // host until cleared here too.
        Artisan::call('route:clear');

        $this->persistTrackingSettings();

        AdminActivity::log('updated', 'Environment settings updated');

        $this->dispatch('close-modal', name: 'env-save-confirm');
        session()->flash('success', 'Environment settings saved. Configuration cache cleared.');
        $this->js('window.location.reload()');
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

    /**
     * Persists Tracking's Google Pixel ID and the reCAPTCHA enable toggle — the
     * only two plain (DB-backed) Settings hosted on this page — alongside the
     * .env-backed fields above, so a single "Save Environment Settings" click
     * persists both without the admin needing to know they're stored differently.
     */
    private function persistTrackingSettings(): void
    {
        Setting::set('google_pixel_id', $this->settings['google_pixel_id'] ?? '');
        Setting::set('recaptcha_enabled', $this->settings['recaptcha_enabled'] ?? false);
    }

    public function render()
    {
        return view('livewire.admin.env.index')->layout('layouts.admin', ['title' => 'Developer Tools']);
    }
}
