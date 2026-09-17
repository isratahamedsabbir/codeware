<?php

use App\Models\Comment;
use App\Models\Feature;
use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists only approved top-level comments, with their approved replies nested', function () {
    $post = Post::factory()->published()->create();

    $approved = Comment::factory()->approved()->for($post, 'commentable')->create(['body' => 'Approved comment']);
    Comment::factory()->pending()->for($post, 'commentable')->create(['body' => 'Pending comment']);
    Comment::factory()->rejected()->for($post, 'commentable')->create(['body' => 'Rejected comment']);

    Comment::factory()->approved()->for($post, 'commentable')->create([
        'parent_id' => $approved->id,
        'body' => 'Approved reply',
    ]);
    Comment::factory()->pending()->for($post, 'commentable')->create([
        'parent_id' => $approved->id,
        'body' => 'Pending reply',
    ]);

    $response = $this->getJson("/api/v1/comments?commentable_type=post&commentable_id={$post->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', 'Approved comment')
        ->assertJsonCount(1, 'data.0.replies')
        ->assertJsonPath('data.0.replies.0.body', 'Approved reply');
});

it('lists comments for a product and a service the same way', function () {
    $product = Product::factory()->published()->create();
    $service = Service::factory()->published()->create();

    Comment::factory()->approved()->for($product, 'commentable')->create(['body' => 'On the product']);
    Comment::factory()->approved()->for($service, 'commentable')->create(['body' => 'On the service']);

    $this->getJson("/api/v1/comments?commentable_type=product&commentable_id={$product->id}")
        ->assertOk()->assertJsonPath('data.0.body', 'On the product');

    $this->getJson("/api/v1/comments?commentable_type=service&commentable_id={$service->id}")
        ->assertOk()->assertJsonPath('data.0.body', 'On the service');
});

it('rejects an unknown commentable_type', function () {
    $this->getJson('/api/v1/comments?commentable_type=order&commentable_id=1')
        ->assertJsonValidationErrors(['commentable_type']);
});

it('requires a logged-in user to post a comment', function () {
    $post = Post::factory()->published()->create();

    $this->postJson('/api/v1/comments', [
        'commentable_type' => 'post',
        'commentable_id' => $post->id,
        'body' => 'Hello',
    ])->assertUnauthorized();
});

it('creates a pending comment for a logged-in user', function () {
    $post = Post::factory()->published()->create();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/comments', [
        'commentable_type' => 'post',
        'commentable_id' => $post->id,
        'body' => 'My first comment',
    ]);

    $response->assertCreated();

    $comment = Comment::sole();
    expect($comment->status)->toBe('pending')
        ->and($comment->user_id)->toBe($user->id)
        ->and($comment->commentable_type)->toBe(Post::class)
        ->and($comment->commentable_id)->toBe($post->id);
});

it('creates a reply to an approved top-level comment', function () {
    $post = Post::factory()->published()->create();
    $parent = Comment::factory()->approved()->for($post, 'commentable')->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/comments', [
        'commentable_type' => 'post',
        'commentable_id' => $post->id,
        'body' => 'A reply',
        'parent_id' => $parent->id,
    ])->assertCreated();

    expect(Comment::where('parent_id', $parent->id)->sole()->body)->toBe('A reply');
});

it('rejects replying to a reply — only one level of nesting is allowed', function () {
    $post = Post::factory()->published()->create();
    $topLevel = Comment::factory()->approved()->for($post, 'commentable')->create();
    $reply = Comment::factory()->approved()->for($post, 'commentable')->create(['parent_id' => $topLevel->id]);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/comments', [
        'commentable_type' => 'post',
        'commentable_id' => $post->id,
        'body' => 'A reply to a reply',
        'parent_id' => $reply->id,
    ])->assertJsonValidationErrors(['parent_id']);
});

it('rejects a parent_id belonging to a different commentable', function () {
    $post = Post::factory()->published()->create();
    $otherPost = Post::factory()->published()->create();
    $parent = Comment::factory()->approved()->for($otherPost, 'commentable')->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/comments', [
        'commentable_type' => 'post',
        'commentable_id' => $post->id,
        'body' => 'Mismatched reply',
        'parent_id' => $parent->id,
    ])->assertJsonValidationErrors(['parent_id']);
});

it('rejects commenting on a draft post', function () {
    $post = Post::factory()->draft()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/comments', [
        'commentable_type' => 'post',
        'commentable_id' => $post->id,
        'body' => 'Hello',
    ])->assertJsonValidationErrors(['commentable_id']);
});

it('lets a user delete their own comment, cascading its replies', function () {
    $post = Post::factory()->published()->create();
    $owner = User::factory()->create();
    $comment = Comment::factory()->approved()->for($post, 'commentable')->create(['user_id' => $owner->id]);
    $reply = Comment::factory()->approved()->for($post, 'commentable')->create(['parent_id' => $comment->id]);

    Sanctum::actingAs($owner);

    $this->deleteJson("/api/v1/comments/{$comment->id}")->assertOk();

    expect(Comment::find($comment->id))->toBeNull()
        ->and(Comment::find($reply->id))->toBeNull();
});

it('does not let a user delete someone else\'s comment', function () {
    $post = Post::factory()->published()->create();
    $comment = Comment::factory()->approved()->for($post, 'commentable')->create();

    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson("/api/v1/comments/{$comment->id}")->assertNotFound();

    expect(Comment::find($comment->id))->not->toBeNull();
});

it('is blocked when the comments feature is disabled', function () {
    Feature::create(['key' => 'comments', 'label' => 'Comments', 'is_enabled' => false]);

    $this->getJson('/api/v1/comments?commentable_type=post&commentable_id=1')->assertNotFound();
});
