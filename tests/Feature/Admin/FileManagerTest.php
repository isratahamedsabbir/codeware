<?php

use App\Livewire\Admin\FileManager\Index as FileManager;
use App\Models\User;
use App\Support\FileManagerPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// The File Manager reads/writes real files under the project root, so every
// test here operates on throwaway fixture files under storage/app/testing-file-manager
// — never on real source files — and cleans up afterwards.

beforeEach(function () {
    $this->fixtureDir = storage_path('app/testing-file-manager');
    File::ensureDirectoryExists($this->fixtureDir);

    $this->fixtureFile = $this->fixtureDir.'/sample.txt';
    File::put($this->fixtureFile, 'original content');

    $this->relativeFixture = 'storage/app/testing-file-manager/sample.txt';

    $this->destDir = storage_path('app/testing-file-manager-dest');
    File::ensureDirectoryExists($this->destDir);
});

afterEach(function () {
    File::deleteDirectory($this->fixtureDir);
    File::deleteDirectory($this->destDir);
    File::deleteDirectory(storage_path('app/file-manager-backups'));
});

it('blocks non-admin users from the file manager', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.file-manager'))->assertForbidden();
});

it('lets an admin browse the project root', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.file-manager'))
        ->assertOk()
        ->assertSee('composer.json');
});

it('navigates into a subfolder and shows its contents', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class)
        ->call('open', 'storage')
        ->assertSet('path', 'storage')
        ->call('open', 'app')
        ->assertSet('path', 'storage/app')
        ->assertSee('testing-file-manager');
});

it('loads a text file\'s content for editing', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('open', 'sample.txt')
        ->assertSet('selected', $this->relativeFixture)
        ->assertSet('kind', 'text')
        ->assertSet('editable', true)
        ->assertSet('editingContent', 'original content');
});

it('saves edited text file content back to disk', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('open', 'sample.txt')
        ->set('editingContent', 'updated content')
        ->call('saveFile')
        ->assertDispatched('file-manager-saved');

    expect(File::get($this->fixtureFile))->toBe('updated content');
});

it('backs up the previous content before overwriting', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('open', 'sample.txt')
        ->set('editingContent', 'changed')
        ->call('saveFile');

    $backups = File::files(storage_path('app/file-manager-backups'));
    expect($backups)->not->toBeEmpty();
});

it('treats an image extension as an image preview, not editable', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::put($this->fixtureDir.'/photo.png', 'not-real-image-bytes');

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('open', 'photo.png')
        ->assertSet('kind', 'image')
        ->assertSet('editable', false);
});

it('blocks path traversal outside the project root', function () {
    expect(fn () => FileManagerPath::resolve('../../../../etc/passwd'))
        ->toThrow(NotFoundHttpException::class);
});

it('blocks path traversal on the raw file route', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.file-manager.raw', ['path' => '../../../../etc/passwd']))
        ->assertNotFound();
});

it('serves a real file\'s raw bytes for preview', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.file-manager.raw', ['path' => $this->relativeFixture]))
        ->assertOk()
        ->assertHeader('Content-Length', (string) strlen('original content'));
});

it('creates a new folder', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openCreateModal', 'folder')
        ->set('newName', 'new-folder')
        ->call('createEntry')
        ->assertHasNoErrors();

    expect(is_dir($this->fixtureDir.'/new-folder'))->toBeTrue();
});

it('creates a new empty file', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openCreateModal', 'file')
        ->set('newName', 'notes.txt')
        ->call('createEntry')
        ->assertHasNoErrors();

    expect(File::exists($this->fixtureDir.'/notes.txt'))->toBeTrue()
        ->and(File::get($this->fixtureDir.'/notes.txt'))->toBe('');
});

it('rejects a new name containing a slash', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openCreateModal', 'folder')
        ->set('newName', '../escape')
        ->call('createEntry')
        ->assertHasErrors('newName');

    expect(is_dir(dirname($this->fixtureDir).'/escape'))->toBeFalse();
});

it('rejects creating a name that already exists', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openCreateModal', 'file')
        ->set('newName', 'sample.txt')
        ->call('createEntry')
        ->assertHasErrors('newName');

    expect(File::get($this->fixtureFile))->toBe('original content');
});

it('uploads a file into the currently browsed folder', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->set('uploads', [UploadedFile::fake()->create('uploaded.txt', 1)]);

    expect(File::exists($this->fixtureDir.'/uploaded.txt'))->toBeTrue();
});

it('skips an upload whose name already exists in the folder', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->set('uploads', [UploadedFile::fake()->create('sample.txt', 1)]);

    expect(File::get($this->fixtureFile))->toBe('original content');
});

it('zips selected entries into a new archive in the current folder', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('toggleChecked', 'sample.txt')
        ->call('zipSelected');

    $zips = collect(File::files($this->fixtureDir))->filter(fn ($f) => $f->getExtension() === 'zip');
    expect($zips)->toHaveCount(1);

    $zip = new ZipArchive;
    $zip->open($zips->first()->getPathname());
    expect($zip->locateName('sample.txt'))->not->toBeFalse();
    $zip->close();
});

it('extracts an uploaded zip into a new folder named after it', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $zip = new ZipArchive;
    $zip->open($this->fixtureDir.'/bundle.zip', ZipArchive::CREATE);
    $zip->addFromString('inner.txt', 'hello from zip');
    $zip->close();

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('extractZip', 'bundle.zip');

    expect(File::exists($this->fixtureDir.'/bundle/inner.txt'))->toBeTrue()
        ->and(File::get($this->fixtureDir.'/bundle/inner.txt'))->toBe('hello from zip');
});

it('refuses to extract a zip whose entries escape the target folder', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $zip = new ZipArchive;
    $zip->open($this->fixtureDir.'/evil.zip', ZipArchive::CREATE);
    $zip->addFromString('../../escaped.txt', 'pwned');
    $zip->close();

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('extractZip', 'evil.zip');

    expect(File::exists(storage_path('app/escaped.txt')))->toBeFalse()
        ->and(is_dir($this->fixtureDir.'/evil'))->toBeFalse();
});

it('forces a download disposition when the download flag is set', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $response = $this->get(route('admin.file-manager.raw', [
        'path' => $this->relativeFixture,
        'download' => 1,
    ]));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

it('renames a file', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openRenameModal', 'sample.txt')
        ->set('renameNewName', 'renamed.txt')
        ->call('renameEntry')
        ->assertHasNoErrors();

    expect(File::exists($this->fixtureDir.'/renamed.txt'))->toBeTrue()
        ->and(File::exists($this->fixtureFile))->toBeFalse()
        ->and(File::get($this->fixtureDir.'/renamed.txt'))->toBe('original content');
});

it('rejects renaming to a name that already exists', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::put($this->fixtureDir.'/other.txt', 'other');

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openRenameModal', 'sample.txt')
        ->set('renameNewName', 'other.txt')
        ->call('renameEntry')
        ->assertHasErrors('renameNewName');

    expect(File::exists($this->fixtureFile))->toBeTrue();
});

it('deletes a file after confirmation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('confirmDelete', 'sample.txt')
        ->call('deleteEntry');

    expect(File::exists($this->fixtureFile))->toBeFalse();
});

it('deletes a folder and everything inside it', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::ensureDirectoryExists($this->fixtureDir.'/sub');
    File::put($this->fixtureDir.'/sub/inner.txt', 'inner');

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('confirmDelete', 'sub')
        ->call('deleteEntry');

    expect(is_dir($this->fixtureDir.'/sub'))->toBeFalse();
});

it('moves a file into another folder', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openTransferModal', 'move', 'sample.txt')
        ->set('transferDestination', 'storage/app/testing-file-manager-dest')
        ->call('transferEntry')
        ->assertHasNoErrors();

    expect(File::exists($this->fixtureFile))->toBeFalse()
        ->and(File::exists($this->destDir.'/sample.txt'))->toBeTrue()
        ->and(File::get($this->destDir.'/sample.txt'))->toBe('original content');
});

it('copies a file into another folder, leaving the original in place', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openTransferModal', 'copy', 'sample.txt')
        ->set('transferDestination', 'storage/app/testing-file-manager-dest')
        ->call('transferEntry')
        ->assertHasNoErrors();

    expect(File::exists($this->fixtureFile))->toBeTrue()
        ->and(File::exists($this->destDir.'/sample.txt'))->toBeTrue();
});

it('copies a folder recursively into another folder', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::ensureDirectoryExists($this->fixtureDir.'/sub');
    File::put($this->fixtureDir.'/sub/inner.txt', 'inner');

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openTransferModal', 'copy', 'sub')
        ->set('transferDestination', 'storage/app/testing-file-manager-dest')
        ->call('transferEntry')
        ->assertHasNoErrors();

    expect(File::exists($this->destDir.'/sub/inner.txt'))->toBeTrue()
        ->and(File::exists($this->fixtureDir.'/sub/inner.txt'))->toBeTrue();
});

it('refuses to move a folder into itself', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::ensureDirectoryExists($this->fixtureDir.'/sub');

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openTransferModal', 'move', 'sub')
        ->set('transferDestination', 'storage/app/testing-file-manager/sub')
        ->call('transferEntry')
        ->assertHasErrors('transferDestination');

    expect(is_dir($this->fixtureDir.'/sub'))->toBeTrue();
});

it('rejects moving onto a name that already exists at the destination', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::put($this->destDir.'/sample.txt', 'already here');

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openTransferModal', 'move', 'sample.txt')
        ->set('transferDestination', 'storage/app/testing-file-manager-dest')
        ->call('transferEntry')
        ->assertHasErrors('transferDestination');

    expect(File::get($this->destDir.'/sample.txt'))->toBe('already here')
        ->and(File::exists($this->fixtureFile))->toBeTrue();
});

it('composes a new file with content in the current folder', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openComposeModal')
        ->set('composeName', 'written.txt')
        ->set('composeContent', "line one\nline two")
        ->call('composeFile')
        ->assertHasNoErrors();

    expect(File::get($this->fixtureDir.'/written.txt'))->toBe("line one\nline two");
});

it('composes a new file directly inside a folder without navigating into it', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::ensureDirectoryExists($this->fixtureDir.'/sub');

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openComposeModal', 'sub')
        ->set('composeName', 'inner.txt')
        ->set('composeContent', 'hello')
        ->call('composeFile')
        ->assertHasNoErrors()
        ->assertSet('path', 'storage/app/testing-file-manager');

    expect(File::get($this->fixtureDir.'/sub/inner.txt'))->toBe('hello');
});

it('rejects composing a file with a name that already exists', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openComposeModal')
        ->set('composeName', 'sample.txt')
        ->set('composeContent', 'overwrite attempt')
        ->call('composeFile')
        ->assertHasErrors('composeName');

    expect(File::get($this->fixtureFile))->toBe('original content');
});

it('rejects composing a file with an invalid name', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openComposeModal')
        ->set('composeName', '../escape.txt')
        ->set('composeContent', 'pwned')
        ->call('composeFile')
        ->assertHasErrors('composeName');

    expect(File::exists(storage_path('app/escape.txt')))->toBeFalse();
});

it('always shows the checkbox markup for a manager', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->assertSee('type="checkbox"', false);
});

it('clears the current selection via clearChecked', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('toggleChecked', 'sample.txt')
        ->assertSet('checked', ['sample.txt'])
        ->call('clearChecked')
        ->assertSet('checked', []);
});

it('deletes multiple selected files and folders at once', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::put($this->fixtureDir.'/second.txt', 'second');
    File::ensureDirectoryExists($this->fixtureDir.'/sub');
    File::put($this->fixtureDir.'/sub/inner.txt', 'inner');

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('toggleChecked', 'sample.txt')
        ->call('toggleChecked', 'second.txt')
        ->call('toggleChecked', 'sub')
        ->call('confirmDeleteSelected')
        ->call('deleteSelected')
        ->assertSet('checked', []);

    expect(File::exists($this->fixtureFile))->toBeFalse()
        ->and(File::exists($this->fixtureDir.'/second.txt'))->toBeFalse()
        ->and(is_dir($this->fixtureDir.'/sub'))->toBeFalse();
});

it('closes the open preview if the previewed file is bulk-deleted', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('open', 'sample.txt')
        ->assertSet('selected', $this->relativeFixture)
        ->call('toggleChecked', 'sample.txt')
        ->call('deleteSelected')
        ->assertSet('selected', null);
});

it('does nothing when confirming bulk delete with nothing selected', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('confirmDeleteSelected')
        ->assertSet('deletingSelected', false);

    expect(File::exists($this->fixtureFile))->toBeTrue();
});

it('picks up filesystem changes made outside the component on refresh', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $component = Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager']);
    $component->assertDontSee('added-later.txt');

    File::put($this->fixtureDir.'/added-later.txt', 'new');

    $component->call('$refresh')->assertSee('added-later.txt');
});

it('shows a friendly notice instead of crashing when opening an item that no longer exists', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('open', 'does-not-exist.txt')
        ->assertDispatched('notify')
        ->assertSet('path', 'storage/app/testing-file-manager');
});

it('recovers to the project root instead of crashing when the current folder disappears mid-session', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::ensureDirectoryExists($this->fixtureDir.'/vanishing');

    $component = Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager/vanishing']);
    $component->assertSet('path', 'storage/app/testing-file-manager/vanishing');

    File::deleteDirectory($this->fixtureDir.'/vanishing');

    $component->call('$refresh')
        ->assertSet('path', '')
        ->assertDispatched('notify')
        ->assertOk();
});

it('resolves a path via realpath fallback when the folder exists but realpath cannot confirm it', function () {
    // realpath() itself can't be faked, but this proves the resolver's happy
    // path still returns a real, existing directory rather than throwing.
    expect(FileManagerPath::resolve('storage/app/testing-file-manager'))
        ->toBe(realpath($this->fixtureDir));
});

it('opens a large file (up to 10 MB) in the editor', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $content = str_repeat('a', 9 * 1024 * 1024);
    File::put($this->fixtureDir.'/large.txt', $content);

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('open', 'large.txt')
        ->assertSet('kind', 'text')
        ->assertSet('editable', true)
        ->assertSet('editingContent', $content);
});

it('refuses to open an oversized file in the editor instead of risking a Livewire payload overflow', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    File::put($this->fixtureDir.'/huge.txt', str_repeat('a', 11 * 1024 * 1024));

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('open', 'huge.txt')
        ->assertSet('kind', 'too-large')
        ->assertSet('editable', false)
        ->assertSet('editingContent', '');
});

it('keeps the editable file size cap safely under the configured Livewire payload limit', function () {
    // Documents the relationship this bug was about: the file's content
    // round-trips inside the Livewire request payload on every interaction
    // while open, so the edit cap must leave real headroom under the
    // payload ceiling, not sit right up against it.
    $editCap = (new ReflectionClass(FileManager::class))->getConstant('MAX_EDIT_BYTES');
    $payloadLimit = config('livewire.payload.max_size');

    expect($payloadLimit)->not->toBeNull()
        ->and($editCap)->toBeLessThan((int) ($payloadLimit * 0.75));
});

it('reports a save failure instead of falsely claiming success when the write is blocked', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $component = Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('open', 'sample.txt')
        ->set('editingContent', 'attempted update');

    chmod($this->fixtureFile, 0444);

    try {
        $component->call('saveFile')
            ->assertDispatched('notify')
            ->assertNotDispatched('file-manager-saved');

        expect(File::get($this->fixtureFile))->toBe('original content');
    } finally {
        chmod($this->fixtureFile, 0666);
    }
});

// Note: the shared AdminMiddleware (admin.php's route group) already blocks anyone who
// isn't is_admin=true or doesn't hold the Spatie 'admin' role from *any* /admin/* route —
// that's a pre-existing, codebase-wide boundary this feature doesn't change. So a bare
// Spatie permission alone can never reach these routes; the file-manager gates only ever
// come into play for users who already cleared that outer check. These tests exercise the
// gates directly (the actual logic this task adds) rather than fighting that outer wall.

it('blocks a non-admin, non-permissioned user from the file manager route', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.file-manager'))->assertForbidden();
    $this->get(route('admin.file-manager.raw', ['path' => $this->relativeFixture]))->assertForbidden();
});

it('grants both file manager gates unconditionally to is_admin users', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    expect(Gate::allows('view-file-manager'))->toBeTrue()
        ->and(Gate::allows('manage-file-manager'))->toBeTrue();
});

it('denies both file manager gates to a user with no permission and no admin role', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    expect(Gate::allows('view-file-manager'))->toBeFalse()
        ->and(Gate::allows('manage-file-manager'))->toBeFalse();
});

it('does not crash when the file manager permissions have not been seeded yet', function () {
    // No Permission::findOrCreate(...) here on purpose — simulates a fresh
    // install where RolePermissionSeeder hasn't run yet.
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    expect(Gate::allows('view-file-manager'))->toBeFalse()
        ->and(Gate::allows('manage-file-manager'))->toBeFalse();
});

it('grants view-file-manager but not manage-file-manager to a "view file manager" permission holder', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::findOrCreate('view file manager', 'web');

    $user = User::factory()->create(['is_admin' => false]);
    $user->givePermissionTo('view file manager');
    $this->actingAs($user);

    expect(Gate::allows('view-file-manager'))->toBeTrue()
        ->and(Gate::allows('manage-file-manager'))->toBeFalse();
});

it('grants both gates to a "manage file manager" permission holder, since manage implies view', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::findOrCreate('manage file manager', 'web');

    $user = User::factory()->create(['is_admin' => false]);
    $user->givePermissionTo('manage file manager');
    $this->actingAs($user);

    expect(Gate::allows('view-file-manager'))->toBeTrue()
        ->and(Gate::allows('manage-file-manager'))->toBeTrue();
});

it('lets an admin-role user with the admin role\'s default permissions manage files', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::findOrCreate('manage file manager', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo('manage file manager');

    $user = User::factory()->create(['is_admin' => false]);
    $user->assignRole('admin');
    $this->actingAs($user);

    $this->get(route('admin.file-manager'))->assertOk();

    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->call('openCreateModal', 'file')
        ->set('newName', 'granted.txt')
        ->call('createEntry')
        ->assertHasNoErrors();

    expect(File::exists($this->fixtureDir.'/granted.txt'))->toBeTrue();
});

it('lets an operator restrict an admin-role user\'s file manager access by revoking the permission from the role', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::findOrCreate('manage file manager', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    // The admin role does NOT get 'manage file manager' — simulates an operator
    // having unchecked it for this role in the Roles/Permissions admin screen.

    $user = User::factory()->create(['is_admin' => false]);
    $user->assignRole('admin');
    $this->actingAs($user);

    // Still admitted to the admin area at large (access-admin checks hasRole('admin'))...
    $this->get(route('admin.dashboard'))->assertOk();

    // ...but the file manager's own gate is not satisfied by the role alone.
    $this->get(route('admin.file-manager'))->assertForbidden();
    expect(Gate::allows('manage-file-manager'))->toBeFalse();
});

it('blocks a mutating component action for a user without manage-file-manager, without deleting anything', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    // abort_unless() inside a Livewire action doesn't surface as a catchable PHP
    // exception through ->call() the way it does during initial render — Livewire
    // resolves it internally. What actually matters is the outcome: the mutation
    // must not go through.
    Livewire::test(FileManager::class, ['path' => 'storage/app/testing-file-manager'])
        ->set('deleteTarget', 'sample.txt')
        ->call('deleteEntry');

    expect(File::exists($this->fixtureFile))->toBeTrue();
});
