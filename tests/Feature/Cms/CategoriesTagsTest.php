<?php

use App\Livewire\Admin\PostCategories\Form as PostCategoriesForm;
use App\Livewire\Admin\PostCategories\Index as PostCategoriesIndex;
use App\Models\PostCategory;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

// Categories
it('renders blog categories component', function () {
    Livewire::test(PostCategoriesIndex::class)
        ->assertStatus(200);
});

it('displays existing blog categories', function () {
    PostCategory::factory()->create(['name' => ['en' => 'Laravel', 'bn' => 'Laravel']]);
    Livewire::test(PostCategoriesIndex::class)
        ->assertSee('Laravel');
});

it('can create a blog category', function () {
    Livewire::test(PostCategoriesForm::class)
        ->set('name_en', 'PHP')
        ->set('description_en', 'PHP tutorials')
        ->call('save');

    expect(PostCategory::whereJsonContains('name->en', 'PHP')->exists())->toBeTrue();
});

it('validates blog category name is required', function () {
    Livewire::test(PostCategoriesForm::class)
        ->set('name_en', '')
        ->call('save')
        ->assertHasErrors(['name_en']);
});

it('can delete a blog category', function () {
    $category = PostCategory::factory()->create();
    Livewire::test(PostCategoriesIndex::class)
        ->call('confirmDelete', $category->id)
        ->call('delete');

    expect(PostCategory::find($category->id))->toBeNull();
});
