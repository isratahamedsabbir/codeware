<?php

use App\Livewire\Admin\MediaLibrary\Index as MediaIndex;
use App\Livewire\Admin\MediaLibrary\PickerModal;
use App\Models\MediaLibrary;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders media library index', function () {
    Livewire::test(MediaIndex::class)
        ->assertStatus(200);
});

it('displays media items', function () {
    MediaLibrary::factory()->create(['original_filename' => 'photo.jpg']);

    Livewire::test(MediaIndex::class)
        ->assertSee('photo.jpg');
});

it('deletes media and removes from storage', function () {
    Storage::disk('public')->put('media/test.jpg', 'fake-content');
    $media = MediaLibrary::factory()->create([
        'path' => 'media/test.jpg',
        'disk' => 'public',
    ]);

    Livewire::test(MediaIndex::class)
        ->call('deleteMedia', $media->id);

    expect(MediaLibrary::find($media->id))->toBeNull();
    Storage::disk('public')->assertMissing('media/test.jpg');
});

it('renders picker modal', function () {
    Livewire::test(PickerModal::class)
        ->assertStatus(200);
});

it('picker modal dispatches mediaPickerSelected event after selecting and confirming', function () {
    $media = MediaLibrary::factory()->create(['path' => 'media/img.jpg', 'disk' => 'public']);

    Livewire::test(PickerModal::class)
        ->call('openPicker', 'test-picker')
        ->call('selectMedia', $media->id)
        ->call('confirmSelection')
        ->assertDispatched('mediaPickerSelected');
});

it('assembles a chunked upload into a media library record once all chunks arrive', function () {
    Storage::fake('local');
    $uploadId = 'test-upload-1';

    $this->postJson(route('admin.media-library.chunk-upload'), [
        'chunk' => UploadedFile::fake()->createWithContent('chunk', 'Hello '),
        'chunkIndex' => 0,
        'totalChunks' => 2,
        'uploadId' => $uploadId,
        'filename' => 'bigfile.pdf',
        'allowedExt' => 'pdf',
    ])->assertOk()->assertJson(['done' => false]);

    $response = $this->postJson(route('admin.media-library.chunk-upload'), [
        'chunk' => UploadedFile::fake()->createWithContent('chunk', 'World!'),
        'chunkIndex' => 1,
        'totalChunks' => 2,
        'uploadId' => $uploadId,
        'filename' => 'bigfile.pdf',
        'allowedExt' => 'pdf',
    ]);

    $response->assertOk()->assertJson(['done' => true]);

    $media = MediaLibrary::find($response->json('media.id'));

    expect($media)->not->toBeNull();
    expect($media->original_filename)->toBe('bigfile.pdf');
    expect($media->file_size)->toBe(strlen('Hello World!'));

    Storage::disk('public')->assertExists($media->path);
    expect(Storage::disk('public')->get($media->path))->toBe('Hello World!');
});

it('rejects a disallowed file extension for chunked upload', function () {
    Storage::fake('local');

    $this->postJson(route('admin.media-library.chunk-upload'), [
        'chunk' => UploadedFile::fake()->createWithContent('chunk', 'bad'),
        'chunkIndex' => 0,
        'totalChunks' => 1,
        'uploadId' => 'bad-upload',
        'filename' => 'virus.exe',
        'allowedExt' => 'jpg,png',
    ])->assertStatus(422);
});

it('opens the watermark modal pre-filled with the saved settings', function () {
    Setting::set('watermark_enabled', '1');
    Setting::set('watermark_image', '/storage/watermark.png');
    Setting::set('watermark_position', 'top-left');
    Setting::set('watermark_opacity', '35');

    Livewire::test(MediaIndex::class)
        ->call('openWatermarkModal')
        ->assertSet('showWatermarkModal', true)
        ->assertSet('watermarkEnabled', true)
        ->assertSet('watermarkImage', '/storage/watermark.png')
        ->assertSet('watermarkPosition', 'top-left')
        ->assertSet('watermarkOpacity', 35)
        ->assertSee('Watermark Settings')
        ->assertSee('Watermark Image');
});

it('saves watermark settings from the modal', function () {
    Livewire::test(MediaIndex::class)
        ->call('openWatermarkModal')
        ->set('watermarkEnabled', true)
        ->set('watermarkImage', '/storage/watermark.png')
        ->set('watermarkPosition', 'center')
        ->set('watermarkOpacity', 60)
        ->call('saveWatermark')
        ->assertSet('showWatermarkModal', false)
        ->assertDispatched('notify')
        ->assertHasNoErrors();

    expect(Setting::get('watermark_enabled'))->toBe('1')
        ->and(Setting::get('watermark_image'))->toBe('/storage/watermark.png')
        ->and(Setting::get('watermark_position'))->toBe('center')
        ->and(Setting::get('watermark_opacity'))->toBe('60');
});

it('saves a disabled watermark without losing the other settings', function () {
    Setting::set('watermark_enabled', '1');
    Setting::set('watermark_image', '/storage/watermark.png');
    Setting::set('watermark_position', 'bottom-right');
    Setting::set('watermark_opacity', '50');

    Livewire::test(MediaIndex::class)
        ->call('openWatermarkModal')
        ->set('watermarkEnabled', false)
        ->call('saveWatermark')
        ->assertHasNoErrors();

    expect(Setting::get('watermark_enabled'))->toBe('0')
        ->and(Setting::get('watermark_image'))->toBe('/storage/watermark.png')
        ->and(Setting::get('watermark_position'))->toBe('bottom-right')
        ->and(Setting::get('watermark_opacity'))->toBe('50');
});

it('rejects an invalid watermark position or opacity', function () {
    Livewire::test(MediaIndex::class)
        ->call('openWatermarkModal')
        ->set('watermarkPosition', 'middle')
        ->set('watermarkOpacity', 150)
        ->call('saveWatermark')
        ->assertHasErrors(['watermarkPosition', 'watermarkOpacity']);
});

it('closing the watermark modal dismisses it', function () {
    Livewire::test(MediaIndex::class)
        ->call('openWatermarkModal')
        ->assertSet('showWatermarkModal', true)
        ->call('closeWatermarkModal')
        ->assertSet('showWatermarkModal', false);
});

// -- Storage symlink ----------------------------------------------------------
// public/storage must be a symlink into storage/app/public or the `public` disk
// 404s at /storage/... . These tests redirect filesystems.links at a temp
// directory so they can never rename or delete the developer's real
// public/storage folder (which holds real uploads).

beforeEach(function () {
    $this->link = storage_path('framework/testing/storage-link');
    $this->target = storage_path('framework/testing/storage-target');

    // Belt and braces: these tests rename directories, so refuse to run if the
    // redirect below ever stops taking effect and the path would resolve to the
    // real public/storage folder full of actual uploads.
    expect($this->link)->not->toBe(public_path('storage'));

    File::deleteDirectory(dirname($this->link));
    File::ensureDirectoryExists($this->target);

    config(['filesystems.links' => [$this->link => $this->target]]);
});

/**
 * The button and the action are developer-environment only, and the suite runs
 * with APP_ENV=testing (phpunit.xml), so the tests below opt in explicitly.
 */
function asDeveloperEnvironment(): void
{
    app()->detectEnvironment(fn () => 'developer');
}

afterEach(function () {
    File::deleteDirectory(dirname($this->link));
});

/** Can this machine create a symlink at all? Windows needs Developer Mode or elevation. */
function canCreateSymlinks(): bool
{
    static $can = null;

    return $can ??= (function () {
        $probe = storage_path('framework/testing/symlink-probe');
        File::ensureDirectoryExists(dirname($probe));

        return is_link($probe) || (@symlink(dirname($probe), $probe) && is_link($probe));
    })();
}

it('offers the link button in the developer environment while public/storage is not a symlink', function () {
    // A plain copied directory is what the button exists to fix: is_link() is
    // false for it, so the button must render. Asserted over a real request
    // because the button lives in a @push('page-header-actions') block, which
    // Livewire::test() never flushes into the layout (same for the existing
    // Watermark and Upload Files buttons — assertSee on the component alone
    // cannot see any of them).
    asDeveloperEnvironment();
    File::ensureDirectoryExists($this->link.'/media');

    $this->actingAs($this->admin)
        ->get(route('admin.media-library'))
        ->assertOk()
        ->assertSee('Link Storage')
        ->assertSee('Upload Files')
        // Sits ahead of the two pre-existing header buttons.
        ->assertSeeInOrder(['Link Storage', 'Watermark', 'Upload Files']);
});

it('hides the link button outside the developer environment', function () {
    // No asDeveloperEnvironment() call: this is the deployed case, where the
    // link either already exists or gets fixed on the server instead.
    File::ensureDirectoryExists($this->link.'/media');

    $this->actingAs($this->admin)
        ->get(route('admin.media-library'))
        ->assertOk()
        ->assertDontSee('Link Storage')
        ->assertSee('Upload Files');
});

it('keeps the link button off the staff tier, which cannot run the action', function () {
    asDeveloperEnvironment();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.media-library'))
        ->assertOk()
        ->assertDontSee('Link Storage');
});

it('refuses to create the link for the staff tier', function () {
    asDeveloperEnvironment();
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(MediaIndex::class)
        ->call('createStorageLink')
        ->assertForbidden();
});

it('refuses to create the link outside the developer environment', function () {
    File::ensureDirectoryExists($this->link.'/media');

    Livewire::test(MediaIndex::class)
        ->call('createStorageLink')
        ->assertNotFound();
});

it('reports a clear error when the storage target is missing', function () {
    asDeveloperEnvironment();
    File::deleteDirectory($this->target);

    Livewire::test(MediaIndex::class)
        ->call('createStorageLink')
        ->assertDispatched('notify', type: 'error');
});

it('links storage and keeps the directory it replaced as a timestamped backup', function () {
    asDeveloperEnvironment();

    if (! canCreateSymlinks()) {
        $this->markTestSkipped('This platform cannot create symlinks (Windows: enable Developer Mode or elevate the server).');
    }

    File::ensureDirectoryExists($this->link.'/media');
    File::put($this->link.'/media/keep-me.txt', 'existing upload');

    Livewire::test(MediaIndex::class)
        ->call('createStorageLink')
        ->assertDispatched('notify', type: 'success')
        ->assertRedirect(route('admin.media-library'));

    expect(is_link($this->link))->toBeTrue();

    // Nothing deleted: the replaced directory is renamed aside, not removed.
    $backups = glob($this->link.'.bak-*');
    expect($backups)->toHaveCount(1);
    expect(File::get($backups[0].'/media/keep-me.txt'))->toBe('existing upload');

    // And the link resolves to the real storage tree.
    expect(is_dir($this->link.'/media'))->toBeTrue();
});

it('hides the link button once the symlink exists', function () {
    asDeveloperEnvironment();

    if (! canCreateSymlinks()) {
        $this->markTestSkipped('This platform cannot create symlinks (Windows: enable Developer Mode or elevate the server).');
    }

    symlink($this->target, $this->link);

    $this->actingAs($this->admin)
        ->get(route('admin.media-library'))
        ->assertOk()
        ->assertDontSee('Link Storage');
});

it('leaves the existing directory in place when the symlink cannot be created', function () {
    asDeveloperEnvironment();

    if (canCreateSymlinks()) {
        $this->markTestSkipped('This platform can create symlinks, so the failure path is unreachable here.');
    }

    File::ensureDirectoryExists($this->link.'/media');
    File::put($this->link.'/media/keep-me.txt', 'existing upload');

    Livewire::test(MediaIndex::class)
        ->call('createStorageLink')
        ->assertDispatched('notify', type: 'error');

    // Rolled back: the original directory is back where it started, with its
    // contents, and no stray .bak- directory was left behind.
    expect(File::isDirectory($this->link))->toBeTrue();
    expect(File::get($this->link.'/media/keep-me.txt'))->toBe('existing upload');
    expect(glob($this->link.'.bak-*'))->toBeEmpty();
});
