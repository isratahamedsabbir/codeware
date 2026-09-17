<?php

use App\Livewire\Admin\Comments\Index as CommentsIndex;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('guests and staff are blocked from the comments screen', function () {
    auth()->logout();
    $this->get(route('admin.comments'))->assertRedirect('/login');

    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $this->actingAs($staff)->get(route('admin.comments'))->assertForbidden();
});

it('renders the comments index with existing top-level comments only', function () {
    $post = Post::factory()->published()->create();
    $comment = Comment::factory()->for($post, 'commentable')->create(['body' => 'Top level comment']);
    Comment::factory()->for($post, 'commentable')->create(['parent_id' => $comment->id, 'body' => 'A reply body']);

    Livewire::test(CommentsIndex::class)
        ->assertSee('Top level comment')
        ->assertDontSee('A reply body');
});

it('filters comments by status', function () {
    $post = Post::factory()->published()->create();
    Comment::factory()->approved()->for($post, 'commentable')->create(['body' => 'Approved one']);
    Comment::factory()->pending()->for($post, 'commentable')->create(['body' => 'Pending one']);

    Livewire::test(CommentsIndex::class)
        ->set('statusFilter', 'approved')
        ->assertSee('Approved one')
        ->assertDontSee('Pending one');
});

it('filters comments by commentable type', function () {
    $post = Post::factory()->published()->create();
    $product = Product::factory()->published()->create();
    Comment::factory()->for($post, 'commentable')->create(['body' => 'About the post']);
    Comment::factory()->for($product, 'commentable')->create(['body' => 'About the product']);

    Livewire::test(CommentsIndex::class)
        ->set('typeFilter', 'product')
        ->assertSee('About the product')
        ->assertDontSee('About the post');
});

it('approves a pending comment from the index', function () {
    $post = Post::factory()->published()->create();
    $comment = Comment::factory()->pending()->for($post, 'commentable')->create();

    Livewire::test(CommentsIndex::class)
        ->call('updateStatus', $comment->id, 'approved');

    expect($comment->fresh()->status)->toBe('approved');
});

it('rejects a comment from the index', function () {
    $post = Post::factory()->published()->create();
    $comment = Comment::factory()->pending()->for($post, 'commentable')->create();

    Livewire::test(CommentsIndex::class)
        ->call('updateStatus', $comment->id, 'rejected');

    expect($comment->fresh()->status)->toBe('rejected');
});

it('deletes a comment, cascading its replies', function () {
    $post = Post::factory()->published()->create();
    $comment = Comment::factory()->for($post, 'commentable')->create();
    $reply = Comment::factory()->for($post, 'commentable')->create(['parent_id' => $comment->id]);

    Livewire::test(CommentsIndex::class)
        ->call('confirmDelete', $comment->id)
        ->call('delete');

    expect(Comment::find($comment->id))->toBeNull()
        ->and(Comment::find($reply->id))->toBeNull();
});

it('bulk deletes selected comments', function () {
    $post = Post::factory()->published()->create();
    $comments = Comment::factory()->for($post, 'commentable')->count(3)->create();

    Livewire::test(CommentsIndex::class)
        ->call('toggleSelect', $comments[0]->id)
        ->call('toggleSelect', $comments[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 comments deleted successfully');

    expect(Comment::find($comments[0]->id))->toBeNull()
        ->and(Comment::find($comments[1]->id))->toBeNull()
        ->and(Comment::find($comments[2]->id))->not->toBeNull();
});
