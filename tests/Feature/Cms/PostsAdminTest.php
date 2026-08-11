<?php

use App\Livewire\Admin\Posts\Index as PostsIndex;
use App\Models\BlogCategory;
use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('renders posts index', function () {
    Livewire::test(PostsIndex::class)->assertStatus(200);
});

it('displays posts in the table', function () {
    Post::factory()->create(['title' => ['en' => 'Hello World', 'bn' => 'হ্যালো']]);
    Livewire::test(PostsIndex::class)->assertSee('Hello World');
});

it('can create a post with metadata', function () {
    Livewire::test(PostsIndex::class)
        ->set('title_en', 'My First Post')
        ->set('status', 'inactive')
        ->call('save');

    expect(Post::whereJsonContains('title->en', 'My First Post')->exists())->toBeTrue();
});

it('validates english title is required', function () {
    Livewire::test(PostsIndex::class)
        ->set('title_en', '')
        ->call('save')
        ->assertHasErrors(['title_en']);
});

it('can filter posts by status', function () {
    Post::factory()->active()->create(['title' => ['en' => 'Active Post', 'bn' => '']]);
    Post::factory()->inactive()->create(['title' => ['en' => 'Inactive Post', 'bn' => '']]);

    Livewire::test(PostsIndex::class)
        ->set('statusFilter', 'active')
        ->assertSee('Active Post')
        ->assertDontSee('Inactive Post');
});

it('can soft-delete a post', function () {
    $post = Post::factory()->create();
    Livewire::test(PostsIndex::class)
        ->call('confirmDelete', $post->id)
        ->call('delete');

    expect(Post::find($post->id))->toBeNull();
    expect(Post::withTrashed()->find($post->id))->not->toBeNull();
});

it('dispatches open-puck-editor event when opening puck editor for existing post', function () {
    $post = Post::factory()->create();
    Livewire::test(PostsIndex::class)
        ->call('openPuckEditor', $post->id)
        ->assertDispatched('open-puck-editor');
});

it('dispatches open-puck-editor event when opening puck editor for new post', function () {
    Livewire::test(PostsIndex::class)
        ->call('openPuckEditorNew')
        ->assertDispatched('open-puck-editor');
});
