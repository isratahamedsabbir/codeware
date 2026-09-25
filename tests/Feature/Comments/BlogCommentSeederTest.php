<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\BlogCommentSeeder;
use Database\Seeders\RolePermissionSeeder;

use function Pest\Laravel\seed as pestSeed;

beforeEach(function () {
    pestSeed(RolePermissionSeeder::class);

    $this->users = User::factory()->count(5)->create()->each(
        fn (User $user) => $user->assignRole('customer')
    );

    $this->posts = collect(range(1, 4))->map(fn () => Post::factory()->published()->create([
        'title' => ['en' => 'Post', 'bn' => ''],
    ]));

    $this->posts->values()->each(function (Post $post, int $index) {
        pairPageFor($post, 'post', 'post-'.$index, $this->users->first()->id);
    });
});

it('seeds dummy comments and one-level replies on published posts', function () {
    $this->assertDatabaseCount('comments', 0);

    $this->seed(BlogCommentSeeder::class);

    $comments = Comment::get();

    expect($comments)->not->toBeEmpty();

    foreach ($comments as $comment) {
        expect($comment->commentable_type)->toBe(Post::class);
        expect($comment->commentable_id)->toBeIn($this->posts->pluck('id')->all());
        expect($comment->user->hasRole('customer'))->toBeTrue();
    }

    expect(Comment::where('commentable_type', Post::class)->approved()->exists())->toBeTrue();
});

it('seeds replies one level deep only', function () {
    $this->seed(BlogCommentSeeder::class);

    $replies = Comment::whereNotNull('parent_id')->get();

    expect($replies)->not->toBeEmpty();

    foreach ($replies as $reply) {
        expect($reply->parent?->parent_id)->toBeNull();
    }
});

it('always seeds a pending comment for moderation', function () {
    $this->seed(BlogCommentSeeder::class);

    expect(Comment::where('status', 'pending')->where('commentable_type', Post::class)->exists())->toBeTrue();
});
