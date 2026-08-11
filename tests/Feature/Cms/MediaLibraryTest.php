<?php

use App\Livewire\Admin\MediaLibrary\Index as MediaIndex;
use App\Livewire\Admin\MediaLibrary\PickerModal;
use App\Models\MediaLibrary;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('renders media library index', function () {
    Livewire::test(MediaIndex::class)
        ->assertStatus(200);
});

it('displays media items', function () {
    MediaLibrary::factory()->create(['original_name' => 'photo.jpg']);

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
        ->call('confirmDelete', $media->id)
        ->call('delete');

    expect(MediaLibrary::find($media->id))->toBeNull();
    Storage::disk('public')->assertMissing('media/test.jpg');
});

it('renders picker modal', function () {
    Livewire::test(PickerModal::class)
        ->assertStatus(200);
});

it('picker modal dispatches media-selected event on select', function () {
    $media = MediaLibrary::factory()->create(['path' => 'media/img.jpg', 'disk' => 'public']);

    Livewire::test(PickerModal::class)
        ->call('select', $media->id)
        ->assertDispatched('media-selected');
});
