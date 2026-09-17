<?php

use App\Livewire\Admin\Posts\Form as PostsForm;
use App\Livewire\Admin\Posts\Index as PostsIndex;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
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
    Livewire::test(PostsForm::class)
        ->set('title.en', 'My First Post')
        ->call('save');

    expect(Post::whereJsonContains('title->en', 'My First Post')->exists())->toBeTrue();
});

it('validates english title is required', function () {
    Livewire::test(PostsForm::class)
        ->set('title.en', '')
        ->call('save')
        ->assertHasErrors(['title.en']);
});

it('creates a post-typed tag inline from the form and selects it', function () {
    Livewire::test(PostsForm::class)
        ->set('newTagName', 'Announcement')
        ->call('createTag')
        ->assertSet('newTagName', '');

    $tag = Tag::whereJsonContains('name->en', 'Announcement')->firstOrFail();

    expect($tag->type)->toBe(Tag::TYPE_POST);
});

it('lists both post-typed and product-typed tags in the post form, but not legacy tags', function () {
    Tag::factory()->post()->create(['name' => ['en' => 'Post Tag', 'bn' => '']]);
    Tag::factory()->product()->create(['name' => ['en' => 'Product Tag', 'bn' => '']]);
    Tag::factory()->create(['name' => ['en' => 'Legacy Only', 'bn' => '']]);

    Livewire::test(PostsForm::class)
        ->assertSee('Post Tag')
        ->assertSee('Product Tag')
        ->assertDontSee('Legacy Only');
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

it('opens and closes the view details modal for a post', function () {
    $post = Post::factory()->create(['title' => ['en' => 'Hello World', 'bn' => '']]);

    Livewire::test(PostsIndex::class)
        ->call('viewDetails', $post->id)
        ->assertSet('viewingId', $post->id)
        ->assertSee('Hello World')
        ->call('closeDetails')
        ->assertSet('viewingId', null);
});

it('opens the puck editor for an existing post', function () {
    $post = Post::factory()->create();

    $component = Livewire::test(PostsIndex::class)->call('openPuckEditor', $post->id);

    $xjs = $component->effects['xjs'] ?? [];
    expect($xjs[0]['expression'] ?? null)->toContain('\/puck\/edit\/post\/');
});

it('keeps one post\'s puck editor token valid after opening the editor for a different post', function () {
    $postA = Post::factory()->create();
    $postB = Post::factory()->create();

    Livewire::test(PostsIndex::class)->call('openPuckEditor', $postA->id);
    $pageA = Page::where(['type' => 'post', 'post_id' => $postA->id])->sole();
    expect($this->admin->tokens()->where('name', "puck-builder-{$pageA->id}")->exists())->toBeTrue();

    Livewire::test(PostsIndex::class)->call('openPuckEditor', $postB->id);
    $pageB = Page::where(['type' => 'post', 'post_id' => $postB->id])->sole();

    expect($this->admin->tokens()->where('name', "puck-builder-{$pageA->id}")->exists())->toBeTrue()
        ->and($this->admin->tokens()->where('name', "puck-builder-{$pageB->id}")->exists())->toBeTrue();
});

it('saves and opens the puck editor for a new post', function () {
    $component = Livewire::test(PostsForm::class)
        ->set('title.en', 'Brand New Post')
        ->call('saveAndOpenPageBuilder');

    expect(Post::whereJsonContains('title->en', 'Brand New Post')->exists())->toBeTrue();

    $xjs = $component->effects['xjs'] ?? [];
    expect($xjs[0]['expression'] ?? null)->toContain('\/puck\/edit\/post\/');
});
