<?php

use App\Livewire\Admin\Tags\Form as TagsForm;
use App\Livewire\Admin\Tags\Index as TagsIndex;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
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
        ->set('name_en', 'News')
        ->call('save');

    expect(Tag::whereJsonContains('name->en', 'News')->exists())->toBeTrue();
});

it('validates tag name is required', function () {
    Livewire::test(TagsForm::class)
        ->set('name_en', '')
        ->call('save')
        ->assertHasErrors(['name_en']);
});

it('can edit a tag', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Old', 'bn' => '']]);

    Livewire::test(TagsForm::class, ['id' => $tag->id])
        ->set('name_en', 'Updated')
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
