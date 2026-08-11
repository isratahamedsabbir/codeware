<?php

use App\Livewire\Admin\BlogCategories\Index as BlogCategoriesIndex;
use App\Livewire\Admin\Tags\Index as TagsIndex;
use App\Models\BlogCategory;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

// Categories
it('renders blog categories component', function () {
    Livewire::test(BlogCategoriesIndex::class)
        ->assertStatus(200);
});

it('displays existing blog categories', function () {
    BlogCategory::factory()->create(['name' => ['en' => 'Laravel', 'bn' => 'Laravel']]);
    Livewire::test(BlogCategoriesIndex::class)
        ->assertSee('Laravel');
});

it('can create a blog category', function () {
    Livewire::test(BlogCategoriesIndex::class)
        ->set('name_en', 'PHP')
        ->set('description_en', 'PHP tutorials')
        ->call('save');

    expect(BlogCategory::whereJsonContains('name->en', 'PHP')->exists())->toBeTrue();
});

it('validates blog category name is required', function () {
    Livewire::test(BlogCategoriesIndex::class)
        ->set('name_en', '')
        ->call('save')
        ->assertHasErrors(['name_en']);
});

it('can delete a blog category', function () {
    $category = BlogCategory::factory()->create();
    Livewire::test(BlogCategoriesIndex::class)
        ->call('confirmDelete', $category->id)
        ->call('delete');

    expect(BlogCategory::find($category->id))->toBeNull();
});

// Tags
it('renders tags component', function () {
    Livewire::test(TagsIndex::class)
        ->assertStatus(200);
});

it('can create a tag', function () {
    Livewire::test(TagsIndex::class)
        ->set('name_en', 'Tutorial')
        ->call('save');

    expect(Tag::whereJsonContains('name->en', 'Tutorial')->exists())->toBeTrue();
});

it('can delete a tag', function () {
    $tag = Tag::factory()->create();
    Livewire::test(TagsIndex::class)
        ->call('confirmDelete', $tag->id)
        ->call('delete');

    expect(Tag::find($tag->id))->toBeNull();
});
