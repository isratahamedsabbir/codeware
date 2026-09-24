<?php

use App\Livewire\Frontend\Account\Profile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
    Setting::set('site_theme', 'ecommerce');

    $this->customer = User::factory()->create(['name' => 'Jane Doe']);
    actingAs($this->customer);
});

it('uploads a profile photo from the account profile page', function () {
    Livewire::test(Profile::class)
        ->set('photo', UploadedFile::fake()->image('me.jpg', 300, 300))
        ->assertHasNoErrors()
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('photo', null);

    $path = $this->customer->fresh()->photo;

    expect($path)->toStartWith('profiles/');
    Storage::disk('public')->assertExists($path);
});

it('replaces the old photo file when a new one is uploaded', function () {
    Storage::disk('public')->put('profiles/old.jpg', 'old');
    $this->customer->update(['photo' => 'profiles/old.jpg']);

    Livewire::test(Profile::class)
        ->set('photo', UploadedFile::fake()->image('new.png'))
        ->call('save');

    Storage::disk('public')->assertMissing('profiles/old.jpg');
    expect($this->customer->fresh()->photo)->not->toBe('profiles/old.jpg');
});

it('removes the profile photo', function () {
    Storage::disk('public')->put('profiles/old.jpg', 'old');
    $this->customer->update(['photo' => 'profiles/old.jpg']);

    Livewire::test(Profile::class)
        ->call('removeCurrentPhoto')
        ->call('save');

    Storage::disk('public')->assertMissing('profiles/old.jpg');
    expect($this->customer->fresh()->photo)->toBeNull();
});

it('rejects files that are not images or are too large', function () {
    Livewire::test(Profile::class)
        ->set('photo', UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'))
        ->assertHasErrors(['photo']);

    Livewire::test(Profile::class)
        ->set('photo', UploadedFile::fake()->image('huge.jpg')->size(3000))
        ->assertHasErrors(['photo' => 'max']);

    expect($this->customer->fresh()->photo)->toBeNull();
});

it('shows the profile photo in the storefront header', function () {
    Storage::disk('public')->put('profiles/me.jpg', 'img');
    $this->customer->update(['photo' => 'profiles/me.jpg']);

    $this->get('/')->assertOk()->assertSee(Storage::disk('public')->url('profiles/me.jpg'), false);
});
