<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\User;
use App\Support\EnvFile;
use Livewire\Livewire;

beforeEach(function () {
    // EnvFile must never touch the real project .env during tests — point it at a
    // throwaway file instead, and always restore the override afterwards.
    $this->envPath = sys_get_temp_dir().'/admin-debug-mode-test-'.uniqid().'.env';

    file_put_contents($this->envPath, <<<'ENV'
        APP_NAME="Test App"
        APP_ENV=local
        APP_DEBUG=false
        APP_URL=https://example.test
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

it('reflects config("app.debug") on mount', function () {
    config(['app.debug' => false]);

    Livewire::test(SettingsIndex::class)
        ->assertSet('debugMode', false);

    config(['app.debug' => true]);

    Livewire::test(SettingsIndex::class)
        ->assertSet('debugMode', true);
});

it('opens a confirmation modal before enabling debug mode, without writing anything yet', function () {
    Livewire::test(SettingsIndex::class)
        ->call('confirmEnableDebugMode')
        ->assertDispatched('open-modal', name: 'debug-mode-confirm');

    expect(EnvFile::get('APP_DEBUG'))->toBe('false');
});

it('enables debug mode, writes APP_DEBUG=true to .env, and reflects that back on the component', function () {
    Livewire::test(SettingsIndex::class)
        ->call('enableDebugMode')
        ->assertSet('debugMode', true)
        ->assertDispatched('close-modal', name: 'debug-mode-confirm');

    expect(EnvFile::get('APP_DEBUG'))->toBe('true');
});

it('disables debug mode immediately, with no confirmation step', function () {
    EnvFile::set(['APP_DEBUG' => 'true']);
    config(['app.debug' => true]);

    Livewire::test(SettingsIndex::class)
        ->assertSet('debugMode', true)
        ->call('disableDebugMode')
        ->assertSet('debugMode', false);

    expect(EnvFile::get('APP_DEBUG'))->toBe('false');
});

it('surfaces a clear error instead of a false success when the write fails', function () {
    $component = Livewire::test(SettingsIndex::class);

    // Break the path only now, after mount() already succeeded, so the write itself
    // is what fails — not component setup.
    EnvFile::$pathOverride = sys_get_temp_dir().'/nonexistent-dir-'.uniqid().'/.env';

    $component->call('enableDebugMode')
        ->assertSet('debugMode', false)
        ->assertDispatched('notify', message: 'Could not update debug mode: Could not read '.EnvFile::path().'.');
});
