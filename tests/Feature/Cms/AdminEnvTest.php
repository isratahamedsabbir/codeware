<?php

use App\Livewire\Admin\Env\Index as EnvIndex;
use App\Models\User;
use App\Support\EnvFile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Collection;
use Livewire\Livewire;

beforeEach(function () {
    // EnvFile must never touch the real project .env during tests — point it at a
    // throwaway file instead, and always restore the override afterwards.
    $this->envPath = sys_get_temp_dir().'/admin-menu-test-'.uniqid().'.env';

    file_put_contents($this->envPath, <<<'ENV'
        # A comment that must survive writes untouched
        APP_NAME="Test App"
        APP_ENV=local
        APP_DEBUG=true
        APP_URL=https://example.test
        FRONTEND_URL=https://frontend.example.test
        APP_LOCALE=en

        LOG_CHANNEL=stack
        LOG_LEVEL=debug

        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=testing
        DB_USERNAME=root
        DB_PASSWORD=

        MAIL_MAILER=log
        MAIL_HOST=
        MAIL_PORT=
        MAIL_USERNAME=
        MAIL_PASSWORD=
        MAIL_FROM_ADDRESS="hello@example.test"
        MAIL_FROM_NAME="Test App"

        CACHE_STORE=file

        APP_KEY=base64:untouchedsecretkeyvalue==
        ENV);

    EnvFile::$pathOverride = $this->envPath;

    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

afterEach(function () {
    EnvFile::$pathOverride = null;
    @unlink($this->envPath);
});

it('parses env file values, unquoting where needed', function () {
    $values = EnvFile::all();

    expect($values['APP_NAME'])->toBe('Test App')
        ->and($values['APP_ENV'])->toBe('local')
        ->and($values['DB_PASSWORD'])->toBe('')
        ->and($values['MAIL_FROM_NAME'])->toBe('Test App')
        ->and($values['APP_KEY'])->toBe('base64:untouchedsecretkeyvalue==');
});

it('updates only the given keys and leaves comments, blank lines, and other keys untouched', function () {
    EnvFile::set(['APP_NAME' => 'New Name', 'DB_HOST' => 'db.example.test']);

    $raw = file_get_contents($this->envPath);

    expect($raw)->toContain('# A comment that must survive writes untouched')
        ->and($raw)->toContain('APP_NAME="New Name"')
        ->and($raw)->toContain('DB_HOST=db.example.test')
        // Untouched keys keep their original value.
        ->and($raw)->toContain('APP_ENV=local')
        ->and($raw)->toContain('APP_KEY=base64:untouchedsecretkeyvalue==');
});

it('quotes values that contain spaces when writing', function () {
    EnvFile::set(['APP_NAME' => 'My Cool App']);

    expect(EnvFile::get('APP_NAME'))->toBe('My Cool App')
        ->and(file_get_contents($this->envPath))->toContain('APP_NAME="My Cool App"');
});

it('backs up the file before writing', function () {
    // Isolated from any backups left by other tests/runs, so the count assertion below
    // isn't at the mercy of the 20-backup retention cap.
    $backupDir = storage_path('app/env-backups');
    Collection::make(glob($backupDir.'/env-*.env') ?: [])->each(fn (string $file) => @unlink($file));

    EnvFile::set(['APP_NAME' => 'Backed Up']);

    expect(glob($backupDir.'/env-*.env') ?: [])->toHaveCount(1);
});

it('renders the env page with app name, debug mode, and urls, never exposing APP_KEY or MySQL', function () {
    $response = $this->get(route('admin.env'));

    $response->assertOk();
    $response->assertSee('App Name');
    $response->assertSee('Debug Mode');
    $response->assertSee('App URL');
    $response->assertSee('Frontend URL');
    $response->assertDontSee('MySQL');
    $response->assertDontSee('untouchedsecretkeyvalue');
});

it('no longer exposes an editable APP_ENV field on the env page — it lives on Settings > General now', function () {
    $component = Livewire::test(EnvIndex::class);

    expect($component->get('env'))->not->toHaveKey('APP_ENV');
});

it('can save environment settings and clears the config cache, leaving MySQL, APP_KEY, and APP_DEBUG untouched', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.APP_NAME', 'Renamed App')
        ->call('confirmSaveEnv')
        ->call('saveEnv');

    expect(EnvFile::get('APP_NAME'))->toBe('Renamed App')
        // MySQL was removed from the editable fields entirely, so it must be untouched.
        ->and(EnvFile::get('DB_HOST'))->toBe('127.0.0.1')
        ->and(EnvFile::get('DB_DATABASE'))->toBe('testing')
        // APP_KEY was never part of the form, so it must be untouched.
        ->and(EnvFile::get('APP_KEY'))->toBe('base64:untouchedsecretkeyvalue==')
        // Debug mode has its own dedicated toggle (see DebugModeTest.php) — it must
        // never be touched by the generic env-save form, so it isn't at risk of
        // flipping accidentally alongside an unrelated env change.
        ->and(EnvFile::get('APP_DEBUG'))->toBe('true')
        // APP_ENV moved to Settings > General (see SettingsAdminTest.php) — must be
        // untouched by this form too.
        ->and(EnvFile::get('APP_ENV'))->toBe('local');
});

it('rejects an invalid app url before opening the confirm modal', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.APP_URL', 'not-a-url')
        ->call('confirmSaveEnv')
        ->assertHasErrors(['env.APP_URL']);

    // Nothing should have been written.
    expect(EnvFile::get('APP_URL'))->toBe('https://example.test');
});

it('loads and saves the frontend url', function () {
    Livewire::test(EnvIndex::class)
        ->assertSet('env.FRONTEND_URL', 'https://frontend.example.test')
        ->set('env.FRONTEND_URL', 'https://new-frontend.example.test')
        ->call('confirmSaveEnv')
        ->call('saveEnv');

    expect(EnvFile::get('FRONTEND_URL'))->toBe('https://new-frontend.example.test');
});

it('rejects an invalid frontend url', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.FRONTEND_URL', 'not-a-url')
        ->call('confirmSaveEnv')
        ->assertHasErrors(['env.FRONTEND_URL']);

    expect(EnvFile::get('FRONTEND_URL'))->toBe('https://frontend.example.test');
});

it('allows a blank frontend url', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.FRONTEND_URL', '')
        ->call('confirmSaveEnv')
        ->call('saveEnv')
        ->assertHasNoErrors();

    expect(EnvFile::get('FRONTEND_URL'))->toBe('');
});

it('saves the vendor portal url', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.VENDOR_URL', 'https://vendor.example.test')
        ->call('confirmSaveEnv')
        ->call('saveEnv')
        ->assertHasNoErrors();

    expect(EnvFile::get('VENDOR_URL'))->toBe('https://vendor.example.test');
});

it('rejects an invalid vendor portal url', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.VENDOR_URL', 'not-a-url')
        ->call('confirmSaveEnv')
        ->assertHasErrors(['env.VENDOR_URL']);
});

it('allows a blank vendor portal url', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.VENDOR_URL', '')
        ->call('confirmSaveEnv')
        ->call('saveEnv')
        ->assertHasNoErrors();

    expect(EnvFile::get('VENDOR_URL'))->toBe('');
});

it('loads and saves the cms editor base url', function () {
    Livewire::test(EnvIndex::class)
        ->assertSet('env.CMS_EDITOR_BASE_URL', '')
        ->set('env.CMS_EDITOR_BASE_URL', 'https://editor.example.test')
        ->call('confirmSaveEnv')
        ->call('saveEnv')
        ->assertHasNoErrors();

    expect(EnvFile::get('CMS_EDITOR_BASE_URL'))->toBe('https://editor.example.test');
});

it('rejects an invalid cms editor base url', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.CMS_EDITOR_BASE_URL', 'not-a-url')
        ->call('confirmSaveEnv')
        ->assertHasErrors(['env.CMS_EDITOR_BASE_URL']);

    expect(EnvFile::get('CMS_EDITOR_BASE_URL'))->toBeNull();
});

it('allows a blank cms editor base url', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.CMS_EDITOR_BASE_URL', '')
        ->call('confirmSaveEnv')
        ->call('saveEnv')
        ->assertHasNoErrors();

    expect(EnvFile::get('CMS_EDITOR_BASE_URL'))->toBe('');
});

it('shows each section note in a modal opened from an info icon on its card', function () {
    $infoKeys = ['app', 'google-login', 'facebook-login', 'pixel', 'recaptcha', 'google-maps', 'aws-s3', 'firebase', 'cms-editor'];

    $this->get(route('admin.env'))
        ->assertOk()
        ->assertSee('data-modal="env-info"', false)
        ->assertSee('data-modal="env-save-confirm"', false);

    foreach ($infoKeys as $infoKey) {
        $this->get(route('admin.env'))
            ->assertSee(sprintf('openInfo(\'%s\')', $infoKey), false);
    }

    Livewire::test(EnvIndex::class)
        ->call('openInfo', 'google-login')
        ->assertSet('infoKey', 'google-login')
        ->assertDispatched('open-modal', name: 'env-info');
});

it('rejects unknown info keys', function () {
    Livewire::test(EnvIndex::class)
        ->call('openInfo', 'not-a-section')
        ->assertSet('infoKey', null);
});

it('renders the integration status overview grid and sticky header', function () {
    $this->get(route('admin.env'))
        ->assertOk()
        ->assertSee('Environment Settings', false)
        ->assertSee('Integration status', false)
        ->assertSee('Configured', false)
        ->assertSee('Not configured', false)
        ->assertSee("jumpTo('general', 'app')", false)
        ->assertSee("highlighted === 'app'", false)
        ->assertSee("highlighted === 'google-maps'", false)
        ->assertSee('Save Environment Settings', false);
});

it('computes per-section configuration status for the overview grid', function () {
    Livewire::test(EnvIndex::class)
        ->set('env.GOOGLE_CLIENT_ID', 'abc')
        ->set('env.GOOGLE_CLIENT_SECRET', 'def')
        ->set('env.RECAPTCHA_SITE_KEY', 'site')
        ->call('sectionStatuses')
        ->assertReturned(fn ($result) => is_array($result)
            && $result['app']['state'] === 'configured'
            && $result['google-login']['state'] === 'configured'
            && $result['recaptcha']['state'] === 'partial'
            && $result['pixel']['state'] === 'empty'
            && $result['google-login']['tab'] === 'authentication'
            && $result['pixel']['tab'] === 'integrations');
});

it('leaves a line completely untouched, quoting style included, when its value did not change', function () {
    // Passing back MAIL_FROM_NAME's own current value must not rewrite its line at all —
    // otherwise every save silently strips quotes from every untouched field, which for a
    // value using ${VAR} interpolation syntax can change its meaning.
    EnvFile::set(['MAIL_FROM_NAME' => EnvFile::get('MAIL_FROM_NAME'), 'APP_NAME' => 'Changed']);

    $raw = file_get_contents($this->envPath);

    expect($raw)->toContain('MAIL_FROM_NAME="Test App"')
        ->and($raw)->toContain('APP_NAME=Changed');
});

it('preserves the file\'s CRLF line endings when writing', function () {
    file_put_contents($this->envPath, "APP_NAME=Original\r\nAPP_ENV=local\r\n");

    EnvFile::set(['APP_NAME' => 'Updated']);

    expect(file_get_contents($this->envPath))->toContain("APP_NAME=Updated\r\n");
});

it('surfaces a clear error instead of a false success when the write fails', function () {
    $component = Livewire::test(EnvIndex::class)->set('env.APP_NAME', 'Renamed App');

    // Break the path only now, after mount()/loadEnv() already succeeded, so the write
    // itself is what fails — not component setup.
    EnvFile::$pathOverride = sys_get_temp_dir().'/nonexistent-dir-'.uniqid().'/.env';

    // The write failure must be surfaced as an error notification, not silently
    // treated as success (and not the generic "saved" message from the happy path).
    // EnvFile::set() reads the file before it writes, so against a path whose
    // directory doesn't exist, the read is what actually fails first.
    $component->call('confirmSaveEnv')->call('saveEnv')
        ->assertDispatched('notify', message: 'Could not save environment settings: Could not read '.EnvFile::path().'.');
});
