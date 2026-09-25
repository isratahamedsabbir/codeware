<?php

use App\Livewire\Frontend\BlogComments;
use App\Models\Comment;
use App\Models\Feature;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

it('renders approved comments with their approved replies, hiding pending and rejected', function () {
    $post = Post::factory()->published()->create();

    $approved = Comment::factory()->approved()->for($post, 'commentable')->create(['body' => 'Approved comment']);
    Comment::factory()->approved()->for($post, 'commentable')->create(['parent_id' => $approved->id, 'body' => 'Approved reply']);
    Comment::factory()->pending()->for($post, 'commentable')->create(['body' => 'Pending comment']);
    Comment::factory()->rejected()->for($post, 'commentable')->create(['body' => 'Rejected comment']);

    Livewire::test(BlogComments::class, ['postId' => $post->id])
        ->assertSee('Approved comment')
        ->assertSee('Approved reply')
        ->assertDontSee('Pending comment')
        ->assertDontSee('Rejected comment');
});

it('blocks guests from posting and prompts them to sign in', function () {
    $post = Post::factory()->published()->create();

    Livewire::test(BlogComments::class, ['postId' => $post->id])
        ->assertSee('Sign in')
        ->call('submit')
        ->assertStatus(403);

    expect(Comment::count())->toBe(0);
});

it('lets any signed-in user post a comment, created as pending', function () {
    $post = Post::factory()->published()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BlogComments::class, ['postId' => $post->id])
        ->set('body', 'My comment')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('body', '');

    $comment = Comment::sole();

    expect($comment->commentable_id)->toBe($post->id)
        ->and($comment->commentable_type)->toBe(Post::class)
        ->and($comment->user_id)->toBe($user->id)
        ->and($comment->parent_id)->toBeNull()
        ->and($comment->status)->toBe('pending');
});

it('lets any signed-in user reply to any top-level comment', function () {
    $post = Post::factory()->published()->create();
    $comment = Comment::factory()->approved()->for($post, 'commentable')->create();
    $replier = User::factory()->create();

    Livewire::actingAs($replier)
        ->test(BlogComments::class, ['postId' => $post->id])
        ->set('replyBody', 'A reply')
        ->call('submitReply', $comment->id)
        ->assertHasNoErrors();

    $reply = Comment::where('parent_id', $comment->id)->sole();

    expect($reply->user_id)->toBe($replier->id)
        ->and($reply->status)->toBe('pending');
});

it('rejects replies deeper than one level', function () {
    $post = Post::factory()->published()->create();
    $comment = Comment::factory()->approved()->for($post, 'commentable')->create();
    Comment::factory()->approved()->for($post, 'commentable')->create(['parent_id' => $comment->id]);

    Livewire::actingAs(User::factory()->create())
        ->test(BlogComments::class, ['postId' => $post->id])
        ->set('replyBody', 'Too deep')
        ->call('submitReply', Comment::query()->whereNotNull('parent_id')->sole()->id)
        ->assertStatus(404);

    expect(Comment::count())->toBe(2);
});

it('shows the comment section on the blog post page', function () {
    $post = Post::factory()->published()->create(['title' => ['en' => 'Discussion Post', 'bn' => '']]);
    pairPageFor($post, 'post', 'discussion-post', $this->admin->id);
    Comment::factory()->approved()->for($post, 'commentable')->create(['body' => 'A reader comment']);

    get('/blog/discussion-post')
        ->assertOk()
        ->assertSee('Comments', false)
        ->assertSee('A reader comment');
});

it('hides the comment section when the comments feature is disabled', function () {
    Feature::create(['key' => 'comments', 'label' => 'Comments (Posts, Products & Services)', 'is_enabled' => false]);

    $post = Post::factory()->published()->create(['title' => ['en' => 'Quiet Post', 'bn' => '']]);
    pairPageFor($post, 'post', 'quiet-post', $this->admin->id);
    Comment::factory()->approved()->for($post, 'commentable')->create(['body' => 'A reader comment']);

    get('/blog/quiet-post')
        ->assertOk()
        ->assertDontSee('Leave a comment')
        ->assertDontSee('A reader comment');
});
