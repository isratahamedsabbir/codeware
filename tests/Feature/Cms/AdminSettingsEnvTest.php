<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\User;
use App\Support\EnvFile;
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

        APP_KEY=base64:untouchedsecretkeyvalue==
        ENV);

    EnvFile::$pathOverride = $this->envPath;

    $this->admin = User::factory()->create(['is_admin' => true]);
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

it('renders the env tab with app name, environment, debug mode, and urls, never exposing APP_KEY or MySQL', function () {
    $response = $this->get(route('admin.settings'));

    $response->assertOk();
    $response->assertSee('Env');
    $response->assertSee('App Name');
    $response->assertSee('Environment');
    $response->assertSee('Debug Mode');
    $response->assertSee('App URL');
    $response->assertSee('Frontend URL');
    $response->assertDontSee('MySQL');
    $response->assertDontSee('untouchedsecretkeyvalue');
});

it('can save environment settings and clears the config cache, leaving MySQL, APP_KEY, and APP_DEBUG untouched', function () {
    Livewire::test(SettingsIndex::class)
        ->set('env.APP_NAME', 'Renamed App')
        ->set('env.APP_ENV', 'staging')
        ->call('confirmSaveEnv')
        ->call('saveEnv');

    expect(EnvFile::get('APP_NAME'))->toBe('Renamed App')
        ->and(EnvFile::get('APP_ENV'))->toBe('staging')
        // MySQL was removed from the editable fields entirely, so it must be untouched.
        ->and(EnvFile::get('DB_HOST'))->toBe('127.0.0.1')
        ->and(EnvFile::get('DB_DATABASE'))->toBe('testing')
        // APP_KEY was never part of the form, so it must be untouched.
        ->and(EnvFile::get('APP_KEY'))->toBe('base64:untouchedsecretkeyvalue==')
        // Debug mode has its own dedicated toggle (see DebugModeTest.php) — it must
        // never be touched by the generic env-save form, so it isn't at risk of
        // flipping accidentally alongside an unrelated env change.
        ->and(EnvFile::get('APP_DEBUG'))->toBe('true');
});

it('accepts developer as a valid environment option', function () {
    Livewire::test(SettingsIndex::class)
        ->set('env.APP_ENV', 'developer')
        ->call('confirmSaveEnv')
        ->assertHasNoErrors(['env.APP_ENV']);
});

it('rejects an unknown environment value', function () {
    Livewire::test(SettingsIndex::class)
        ->set('env.APP_ENV', 'not-a-real-env')
        ->call('confirmSaveEnv')
        ->assertHasErrors(['env.APP_ENV']);

    expect(EnvFile::get('APP_ENV'))->toBe('local');
});

it('rejects an invalid app url before opening the confirm modal', function () {
    Livewire::test(SettingsIndex::class)
        ->set('env.APP_URL', 'not-a-url')
        ->call('confirmSaveEnv')
        ->assertHasErrors(['env.APP_URL']);

    // Nothing should have been written.
    expect(EnvFile::get('APP_URL'))->toBe('https://example.test');
});

it('loads and saves the frontend url', function () {
    Livewire::test(SettingsIndex::class)
        ->assertSet('env.FRONTEND_URL', 'https://frontend.example.test')
        ->set('env.FRONTEND_URL', 'https://new-frontend.example.test')
        ->call('confirmSaveEnv')
        ->call('saveEnv');

    expect(EnvFile::get('FRONTEND_URL'))->toBe('https://new-frontend.example.test');
});

it('rejects an invalid frontend url', function () {
    Livewire::test(SettingsIndex::class)
        ->set('env.FRONTEND_URL', 'not-a-url')
        ->call('confirmSaveEnv')
        ->assertHasErrors(['env.FRONTEND_URL']);

    expect(EnvFile::get('FRONTEND_URL'))->toBe('https://frontend.example.test');
});

it('allows a blank frontend url', function () {
    Livewire::test(SettingsIndex::class)
        ->set('env.FRONTEND_URL', '')
        ->call('confirmSaveEnv')
        ->call('saveEnv')
        ->assertHasNoErrors();

    expect(EnvFile::get('FRONTEND_URL'))->toBe('');
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
    $component = Livewire::test(SettingsIndex::class)->set('env.APP_NAME', 'Renamed App');

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
