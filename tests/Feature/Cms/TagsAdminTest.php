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

it('shows a "Both" badge, not "Legacy", for a shared (null or legacy-typed) tag on the index', function () {
    Tag::factory()->create(['name' => ['en' => 'Null Pool', 'bn' => ''], 'type' => null]);
    Tag::factory()->create(['name' => ['en' => 'Old Pool', 'bn' => ''], 'type' => Tag::TYPE_LEGACY]);

    $html = Livewire::test(TagsIndex::class)->html();

    expect($html)->toContain('Both')
        ->and($html)->not->toContain('Legacy');
});

it('filters the index to shared tags only, covering both null and legacy-typed rows', function () {
    Tag::factory()->create(['name' => ['en' => 'Null Pool', 'bn' => ''], 'type' => null]);
    Tag::factory()->create(['name' => ['en' => 'Old Pool', 'bn' => ''], 'type' => Tag::TYPE_LEGACY]);
    Tag::factory()->create(['name' => ['en' => 'Product Only', 'bn' => ''], 'type' => Tag::TYPE_PRODUCT]);

    Livewire::test(TagsIndex::class)
        ->set('typeFilter', 'shared')
        ->assertSee('Null Pool')
        ->assertSee('Old Pool')
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

it('can create a product-type tag from the tags form', function () {
    Livewire::test(TagsForm::class)
        ->set('name.en', 'Gadget')
        ->set('type', Tag::TYPE_PRODUCT)
        ->call('save');

    expect(Tag::whereJsonContains('name->en', 'Gadget')->firstOrFail()->type)->toBe(Tag::TYPE_PRODUCT);
});

it('can create a shared (null-type) tag by picking the Shared option from the tags form', function () {
    Livewire::test(TagsForm::class)
        ->set('name.en', 'Seasonal')
        ->set('type', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Tag::whereJsonContains('name->en', 'Seasonal')->firstOrFail()->type)->toBeNull();
});

it('loads an existing shared (null-type) tag with the Shared option selected', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Seasonal', 'bn' => ''], 'type' => null]);

    Livewire::test(TagsForm::class, ['id' => $tag->id])
        ->assertSet('type', '');
});

it('loads a legacy-typed tag with the Shared option selected, no separate Legacy option offered', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Old Stock', 'bn' => ''], 'type' => Tag::TYPE_LEGACY]);

    Livewire::test(TagsForm::class, ['id' => $tag->id])
        ->assertSet('type', '')
        ->assertDontSee('Legacy');
});

it('migrates a legacy-typed tag to a real null type once resaved from the form', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Old Stock', 'bn' => ''], 'type' => Tag::TYPE_LEGACY]);

    Livewire::test(TagsForm::class, ['id' => $tag->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($tag->refresh()->type)->toBeNull();
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

it('can delete a tag', function () {
    $tag = Tag::factory()->create();
    Livewire::test(TagsIndex::class)
        ->call('confirmDelete', $tag->id)
        ->call('delete');

    expect(Tag::find($tag->id))->toBeNull();
});
