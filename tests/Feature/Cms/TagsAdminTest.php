<?php

use App\Livewire\Admin\Tags\Form as TagsForm;
use App\Livewire\Admin\Tags\Index as TagsIndex;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders tags index component', function () {
    Livewire::test(TagsIndex::class)
        ->assertStatus(200);
});

it('displays existing tags', function () {
    Tag::factory()->create(['name' => ['en' => 'Laravel', 'bn' => 'Laravel']]);
    Livewire::test(TagsIndex::class)
        ->assertSee('Laravel');
});

it('can create a tag', function () {
    Livewire::test(TagsForm::class)
        ->set('name.en', 'News')
        ->call('save');

    expect(Tag::whereJsonContains('name->en', 'News')->exists())->toBeTrue();
});

it('can create a product-type tag from the tags form', function () {
    Livewire::test(TagsForm::class)
        ->set('name.en', 'Gadget')
        ->set('type', Tag::TYPE_PRODUCT)
        ->call('save');

    expect(Tag::whereJsonContains('name->en', 'Gadget')->firstOrFail()->type)->toBe(Tag::TYPE_PRODUCT);
});

it('validates tag name is required', function () {
    Livewire::test(TagsForm::class)
        ->set('name.en', '')
        ->call('save')
        ->assertHasErrors(['name.en']);
});

it('can edit a tag', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Old', 'bn' => '']]);

    Livewire::test(TagsForm::class, ['id' => $tag->id])
        ->set('name.en', 'Updated')
        ->call('save');

    expect($tag->refresh()->getTranslation('name', 'en', false))->toBe('Updated');
});

it('can delete a tag', function () {
    $tag = Tag::factory()->create();
    Livewire::test(TagsIndex::class)
        ->call('confirmDelete', $tag->id)
        ->call('delete');

    expect(Tag::find($tag->id))->toBeNull();
});
