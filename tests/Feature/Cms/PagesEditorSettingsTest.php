<?php

use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Models\User;
use App\Support\EnvFile;
use App\Support\PuckEditor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    // EnvFile must never touch the real project .env during tests — point it at a
    // throwaway file instead, and always restore the override afterwards.
    $this->envPath = sys_get_temp_dir().'/pages-editor-settings-test-'.uniqid().'.env';

    file_put_contents($this->envPath, <<<'ENV'
        APP_URL=https://example.test
        CMS_EDITOR_BASE_URL=http://127.0.0.1:3002
        PUCK_SESSION=30
        APP_KEY=base64:untouchedsecretkeyvalue==
        ENV);

    EnvFile::$pathOverride = $this->envPath;

    // The modal reads the token expiry through config('cms.*'), which normally
    // comes from PUCK_SESSION in the real .env — pin it so these assertions
    // don't depend on the developer's own value.
    config(['cms.puck_session_minutes' => 30]);

    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

afterEach(function () {
    EnvFile::$pathOverride = null;
    @unlink($this->envPath);
    Artisan::call('config:clear');
});

it('shows both editor values in the settings modal', function () {
    $html = Livewire::test(PagesIndex::class)->html();

    expect($html)->toContain('Editor base URL')
        ->and($html)->toContain('Token expiry (minutes)')
        ->and($html)->toContain('http://127.0.0.1:3002');
});

it('saves the editor base url from the settings modal', function () {
    Livewire::test(PagesIndex::class)
        ->set('editorBaseUrl', 'https://editor.example.test')
        ->call('saveEditorSettings');

    expect(EnvFile::get('CMS_EDITOR_BASE_URL'))->toBe('https://editor.example.test');
});

it('saves the token expiry to the PUCK_SESSION env key', function () {
    Livewire::test(PagesIndex::class)
        ->set('puckSessionMinutes', 15)
        ->call('saveEditorSettings');

    expect(EnvFile::get('PUCK_SESSION'))->toBe('15');
});

it('reads the token expiry back from PUCK_SESSION in env', function () {
    config(['cms.puck_session_minutes' => 90]);

    expect(PuckEditor::sessionMinutes())->toBe(90);
});

it('leaves the untouched env key alone when only one value changes', function () {
    Livewire::test(PagesIndex::class)
        ->set('puckSessionMinutes', 15)
        ->call('saveEditorSettings');

    $lines = file($this->envPath, FILE_IGNORE_NEW_LINES);

    expect(EnvFile::get('CMS_EDITOR_BASE_URL'))->toBe('http://127.0.0.1:3002')
        ->and($lines)->toContain('CMS_EDITOR_BASE_URL=http://127.0.0.1:3002');
});

it('rejects an editor base url that is not a url', function () {
    Livewire::test(PagesIndex::class)
        ->set('editorBaseUrl', 'not-a-url')
        ->call('saveEditorSettings')
        ->assertHasErrors(['editorBaseUrl']);

    expect(EnvFile::get('CMS_EDITOR_BASE_URL'))->toBe('http://127.0.0.1:3002');
});

it('rejects a token expiry outside the allowed range', function () {
    Livewire::test(PagesIndex::class)
        ->set('puckSessionMinutes', 0)
        ->call('saveEditorSettings')
        ->assertHasErrors(['puckSessionMinutes']);

    expect(EnvFile::get('PUCK_SESSION'))->toBe('30');
});

it('hides the whole editor settings modal from staff', function () {
    $staff = User::factory()->staff()->create();

    $html = Livewire::actingAs($staff)->test(PagesIndex::class)->html();

    expect($html)->not->toContain('Editor base URL')
        ->and($html)->not->toContain('Token expiry (minutes)');
});

it('does not let staff write either value through the modal', function () {
    $staff = User::factory()->staff()->create();

    Livewire::actingAs($staff)
        ->test(PagesIndex::class)
        ->set('editorBaseUrl', 'https://evil.example.test')
        ->set('puckSessionMinutes', 15)
        ->call('saveEditorSettings')
        ->assertForbidden();

    expect(EnvFile::get('CMS_EDITOR_BASE_URL'))->toBe('http://127.0.0.1:3002')
        ->and(EnvFile::get('PUCK_SESSION'))->toBe('30');
});
