<?php

use App\Livewire\Admin\Posts\Index as PostsIndex;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $post = Post::factory()->create();

    Livewire::test(PostsIndex::class)
        ->call('toggleSelect', $post->id)
        ->assertSee('Blog')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $posts = Post::factory()->count(2)->create();

    $component = Livewire::test(PostsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $posts[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $post = Post::factory()->create();

    Livewire::test(PostsIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $post->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $post->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a post id in and out of the selection', function () {
    $post = Post::factory()->create();

    Livewire::test(PostsIndex::class)
        ->call('toggleSelect', $post->id)
        ->assertSet('selectedIds', [$post->id])
        ->call('toggleSelect', $post->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(PostsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $post = Post::factory()->create();

    Livewire::test(PostsIndex::class)
        ->call('toggleSelect', $post->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'post-bulk-delete');
});

it('soft-deletes every selected post, cascading its page, and clears the selection', function () {
    $posts = Post::factory()->count(3)->create();
    $keep = Post::factory()->create();

    Livewire::test(PostsIndex::class)
        ->call('toggleSelect', $posts[0]->id)
        ->call('toggleSelect', $posts[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 posts deleted successfully')
        ->assertDispatched('close-modal', name: 'post-bulk-delete');

    expect(Post::find($posts[0]->id))->toBeNull()
        ->and(Post::find($posts[1]->id))->toBeNull()
        ->and(Post::find($posts[2]->id))->not->toBeNull()
        ->and(Post::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one post is deleted', function () {
    $post = Post::factory()->create();

    Livewire::test(PostsIndex::class)
        ->call('toggleSelect', $post->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 post deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $posts = Post::factory()->count(2)->create();

    $component = Livewire::test(PostsIndex::class);

    $component->assertSeeHtml(route('admin.posts.edit', $posts[0]->id));

    $component->call('toggleSelect', $posts[0]->id)
        ->assertDontSeeHtml(route('admin.posts.edit', $posts[0]->id))
        ->assertDontSeeHtml(route('admin.posts.edit', $posts[1]->id));
});

it('exports only the requested post ids as a downloadable csv', function () {
    $included = Post::factory()->create(['title' => ['en' => 'Included Post', 'bn' => '']]);
    $excluded = Post::factory()->create(['title' => ['en' => 'Excluded Post', 'bn' => '']]);

    $response = $this->get(route('admin.posts.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Post')
        ->and($csv)->not->toContain('Excluded Post');
});
