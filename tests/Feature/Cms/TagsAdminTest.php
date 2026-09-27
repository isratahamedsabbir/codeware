<?php

use App\Livewire\Admin\Tags\Form as TagsForm;
use App\Livewire\Admin\Tags\Index as TagsIndex;
use App\Models\Tag;
use App\Models\Type;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    $this->productTypeId = Type::idFor(Type::PRODUCT);
    $this->postTypeId = Type::idFor(Type::POST);
});

it('renders tags index component', function () {
    Livewire::test(TagsIndex::class)
        ->assertStatus(200);
});

it('shows each tag under its own type on the index, with no shared/legacy bucket', function () {
    Tag::factory()->create(['name' => ['en' => 'Product Tag', 'bn' => '']]);
    Tag::factory()->post()->create(['name' => ['en' => 'Post Tag', 'bn' => '']]);

    $html = Livewire::test(TagsIndex::class)->html();

    expect($html)->toContain('Product Tag')
        ->and($html)->toContain('Post Tag')
        ->and($html)->not->toContain('Both')
        ->and($html)->not->toContain('Legacy');
});

it('filters the index to a single type', function () {
    Tag::factory()->create(['name' => ['en' => 'Product Only', 'bn' => '']]);
    Tag::factory()->post()->create(['name' => ['en' => 'Post Only', 'bn' => '']]);

    Livewire::test(TagsIndex::class)
        ->set('typeFilter', (string) $this->postTypeId)
        ->assertSee('Post Only')
        ->assertDontSee('Product Only');
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

it('defaults a new tag to the post pool, as the tags list did before types were a table', function () {
    Livewire::test(TagsForm::class)
        ->assertSet('typeId', $this->postTypeId);
});

it('can create a product-type tag from the tags form', function () {
    Livewire::test(TagsForm::class)
        ->set('name.en', 'Gadget')
        ->set('typeId', $this->productTypeId)
        ->call('save');

    expect(Tag::whereJsonContains('name->en', 'Gadget')->firstOrFail()->type_id)->toBe($this->productTypeId);
});

it('can create a post-type tag from the tags form', function () {
    Livewire::test(TagsForm::class)
        ->set('name.en', 'Seasonal')
        ->set('typeId', $this->postTypeId)
        ->call('save')
        ->assertHasNoErrors();

    expect(Tag::whereJsonContains('name->en', 'Seasonal')->firstOrFail()->type_id)->toBe($this->postTypeId);
});

it('requires a type, since every tag now belongs to exactly one pool', function () {
    Livewire::test(TagsForm::class)
        ->set('name.en', 'Typeless')
        ->set('typeId', null)
        ->call('save')
        ->assertHasErrors('typeId');
});

it('loads an existing tag with its own type selected', function () {
    $tag = Tag::factory()->post()->create(['name' => ['en' => 'Seasonal', 'bn' => '']]);

    Livewire::test(TagsForm::class, ['id' => $tag->id])
        ->assertSet('typeId', $this->postTypeId);
});

it('rejects a duplicate tag name', function () {
    Tag::factory()->create(['name' => ['en' => 'Laravel', 'bn' => '']]);

    Livewire::test(TagsForm::class)
        ->set('name.en', 'Laravel')
        ->call('save')
        ->assertHasErrors(['name.en']);
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

it('can move a tag to the other pool from the form', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Movable', 'bn' => '']]);

    Livewire::test(TagsForm::class, ['id' => $tag->id])
        ->set('typeId', $this->postTypeId)
        ->call('save');

    expect($tag->refresh()->type_id)->toBe($this->postTypeId);
});

it('can delete a tag', function () {
    $tag = Tag::factory()->create();
    Livewire::test(TagsIndex::class)
        ->call('confirmDelete', $tag->id)
        ->call('delete');

    expect(Tag::find($tag->id))->toBeNull();
});
