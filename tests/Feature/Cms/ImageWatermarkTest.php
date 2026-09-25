<?php

use App\Models\MediaLibrary;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    $watermarkImage = imagecreatetruecolor(20, 20);
    imagefill($watermarkImage, 0, 0, imagecolorallocate($watermarkImage, 0, 0, 255));
    ob_start();
    imagepng($watermarkImage);
    $watermarkPng = ob_get_clean();
    imagedestroy($watermarkImage);
    Storage::disk('public')->put('media/watermark-source.png', $watermarkPng);
    Setting::set('watermark_image', Storage::disk('public')->url('media/watermark-source.png'));
    Setting::set('watermark_position', 'bottom-right');
    Setting::set('watermark_opacity', '100');
});

it('leaves the uploaded image untouched when watermarking is disabled', function () {
    Setting::set('watermark_enabled', '0');

    $file = UploadedFile::fake()->image('photo.png', 200, 200);
    $originalBytes = file_get_contents($file->getRealPath());

    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/media', ['file' => $file])
        ->assertCreated();

    $media = MediaLibrary::findOrFail($response->json('data.id'));
    $storedBytes = Storage::disk('public')->get($media->path);

    expect(md5($storedBytes))->toBe(md5($originalBytes));
});

it('stamps the watermark onto the uploaded image when enabled', function () {
    Setting::set('watermark_enabled', '1');

    $file = UploadedFile::fake()->image('photo.png', 200, 200);
    $originalBytes = file_get_contents($file->getRealPath());

    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/media', ['file' => $file])
        ->assertCreated();

    $media = MediaLibrary::findOrFail($response->json('data.id'));
    $storedBytes = Storage::disk('public')->get($media->path);

    expect(md5($storedBytes))->not->toBe(md5($originalBytes));

    $stored = imagecreatefromstring($storedBytes);
    expect($stored)->not->toBeFalse();
    imagedestroy($stored);
});

it('accepts an avif upload and stamps the watermark onto it', function () {
    Setting::set('watermark_enabled', '1');

    $image = imagecreatetruecolor(200, 200);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));
    $path = tempnam(sys_get_temp_dir(), 'avif');
    imageavif($image, $path);
    imagedestroy($image);
    $file = new UploadedFile($path, 'photo.avif', 'image/avif', null, true);
    $originalBytes = file_get_contents($path);

    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/media', ['file' => $file])
        ->assertCreated();

    $media = MediaLibrary::findOrFail($response->json('data.id'));
    $storedBytes = Storage::disk('public')->get($media->path);

    expect($media->mime_type)->toBe('image/avif')
        ->and($media->file_type)->toBe('image')
        ->and(md5($storedBytes))->not->toBe(md5($originalBytes));
});

it('does nothing when no watermark image is configured', function () {
    Setting::set('watermark_enabled', '1');
    Setting::set('watermark_image', '');

    $file = UploadedFile::fake()->image('photo.png', 200, 200);
    $originalBytes = file_get_contents($file->getRealPath());

    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/media', ['file' => $file])
        ->assertCreated();

    $media = MediaLibrary::findOrFail($response->json('data.id'));
    $storedBytes = Storage::disk('public')->get($media->path);

    expect(md5($storedBytes))->toBe(md5($originalBytes));
});
