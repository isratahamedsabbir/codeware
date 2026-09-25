<?php

use App\Livewire\Admin\Advertisements\Form as AdvertisementForm;
use App\Livewire\Admin\Advertisements\Index as AdvertisementsIndex;
use App\Models\Advertisement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders the advertisements index with name, code, clicks and validity', function () {
    Advertisement::factory()->alwaysOn()->create(['name' => 'Summer Blitz']);

    Livewire::test(AdvertisementsIndex::class)
        ->assertSee('Summer Blitz')
        ->assertSee('AD-')
        ->assertSee('Always on');
});

it('creates an advertisement with an auto-generated code and a date window', function () {
    $validFrom = now()->addDays(3)->format('Y-m-d');
    $validUntil = now()->addDays(30)->format('Y-m-d');

    Livewire::test(AdvertisementForm::class)
        ->set('name', 'Spring Sale')
        ->set('image', '/storage/media/banner.png')
        ->set('url', 'https://example.com/spring')
        ->set('validFrom', $validFrom)
        ->set('validUntil', $validUntil)
        ->call('save');

    $ad = Advertisement::where('name', 'Spring Sale')->firstOrFail();

    expect($ad->image)->toBe('/storage/media/banner.png')
        ->and($ad->url)->toBe('https://example.com/spring')
        ->and($ad->clicks)->toBe(0)
        ->and($ad->valid_from->isSameDay(now()->addDays(3)))
        ->and($ad->valid_until->isSameDay(now()->addDays(30)))
        ->and($ad->code)->toMatch('/^AD-[A-Z0-9]{8}$/');
});

it('requires a name and an image', function () {
    Livewire::test(AdvertisementForm::class)
        ->set('name', '')
        ->set('image', '')
        ->call('save')
        ->assertHasErrors(['name', 'image']);

    expect(Advertisement::count())->toBe(0);
});

it('rejects an end date before the start date', function () {
    Livewire::test(AdvertisementForm::class)
        ->set('name', 'Oops')
        ->set('image', '/storage/media/banner.png')
        ->set('validFrom', now()->format('Y-m-d'))
        ->set('validUntil', now()->subDay()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['validUntil']);

    expect(Advertisement::count())->toBe(0);
});

it('edits an advertisement and keeps its code', function () {
    $ad = Advertisement::factory()->create(['name' => 'Old Name']);

    Livewire::test(AdvertisementForm::class, ['id' => $ad->id])
        ->set('name', 'New Name')
        ->call('save');

    expect($ad->refresh())->name->toBe('New Name')
        ->and($ad->code)->toMatch('/^AD-[A-Z0-9]{8}$/');
});

it('deletes an advertisement', function () {
    $ad = Advertisement::factory()->create(['name' => 'Disposable']);

    Livewire::test(AdvertisementsIndex::class)
        ->call('confirmDelete', $ad->id)
        ->call('delete');

    expect(Advertisement::find($ad->id))->toBeNull();
});
