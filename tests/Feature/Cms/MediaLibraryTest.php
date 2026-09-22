<?php

use App\Livewire\Admin\MediaLibrary\Index as MediaIndex;
use App\Livewire\Admin\MediaLibrary\PickerModal;
use App\Models\MediaLibrary;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
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
