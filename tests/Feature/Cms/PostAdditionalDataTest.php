<?php

use App\Livewire\Admin\Posts\Form as PostsForm;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders the Additional Data section with the description editor', function () {
    Livewire::test(PostsForm::class)
        ->assertSee('Additional Data')
        ->assertSee('Rich-text description shown in blog listings and as a fallback');
});

it('hides the Additional Data section when the setting is off', function () {
    Setting::set('additional_data_posts_enabled', '0');
    Cache::flush();

    Livewire::test(PostsForm::class)
        ->assertDontSee('Additional Data');
});

it('persists the rich description on save', function () {
    Livewire::test(PostsForm::class)
        ->set('title.en', 'Descriptive Post')
        ->set('description.en', '<p>Rich post description</p>')
        ->call('save');

    $post = Post::whereJsonContains('title->en', 'Descriptive Post')->firstOrFail();

    expect($post->getTranslations('description'))->toBe(['en' => '<p>Rich post description</p>']);
});

it('hydrates the description back into the edit form', function () {
    $post = Post::factory()->create([
        'title' => ['en' => 'Editable Post', 'bn' => ''],
        'description' => ['en' => '<p>Existing post description</p>', 'bn' => ''],
    ]);
    pairPageFor($post, 'post', 'editable-post', $this->admin->id);

    Livewire::test(PostsForm::class, ['id' => $post->id])
        ->assertSet('description.en', '<p>Existing post description</p>');
});
