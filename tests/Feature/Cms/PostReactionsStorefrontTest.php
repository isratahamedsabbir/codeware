<?php

use App\Livewire\Frontend\PostReactions;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostLike;
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

function reactionsPost(string $slug, array $attributes = []): Post
{
    $post = Post::factory()->published()->create($attributes + ['user_id' => User::factory()->create()->id]);

    pairPageFor($post, 'post', $slug, User::factory()->create()->id);

    return $post;
}

it('shows the author and view count on the blog listing', function () {
    $author = User::factory()->create(['name' => 'Rahim Uddin']);
    reactionsPost('authored-post', [
        'title' => ['en' => 'An Authored Post', 'bn' => ''],
        'description' => ['en' => 'Lead paragraph.', 'bn' => ''],
        'user_id' => $author->id,
        'views' => '999',
    ]);

    get('/blog')
        ->assertOk()
        ->assertSee('Rahim Uddin', false)
        ->assertSee('999', false);
});

it('shows the author plus the reactions bar on the post page', function () {
    $author = User::factory()->create(['name' => 'Rahim Uddin']);
    reactionsPost('authored-post', [
        'title' => ['en' => 'An Authored Post', 'bn' => ''],
        'user_id' => $author->id,
    ]);

    get('/blog/authored-post')
        ->assertOk()
        ->assertSee('Rahim Uddin', false)
        ->assertSee('1 view', false)
        ->assertSee('0 likes', false);
});

it('counts a post view once per visitor', function () {
    $post = reactionsPost('counted-post', ['title' => ['en' => 'Counted Post', 'bn' => '']]);

    expect($post->fresh()->views)->toBe(0);

    $this->withCookies([config('session.cookie') => 'ab12cd34ef56ac78ac90ab12cd34ef56ab12cd34']);

    get('/blog/counted-post')
        ->assertOk()
        ->assertSee('1 view', false);

    $second = get('/blog/counted-post');
    $second->assertOk()->assertSee('1 view', false);

    expect($post->fresh()->views)->toBe(1);
});

it('lets a signed-in user like and unlike a post', function () {
    $post = reactionsPost('liked-post', ['title' => ['en' => 'Liked Post', 'bn' => '']]);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PostReactions::class, ['postId' => $post->id])
        ->assertSee('0 likes', false)
        ->call('toggleLike')
        ->assertSee('1 like', false)
        ->call('toggleLike')
        ->assertSee('0 likes', false);

    expect(PostLike::count())->toBe(0);
});

it('keeps one like per user', function () {
    $post = reactionsPost('liked-post', ['title' => ['en' => 'Liked Post', 'bn' => '']]);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PostReactions::class, ['postId' => $post->id])
        ->call('toggleLike');

    $this->assertDatabaseCount('post_likes', 1);

    Livewire::actingAs($user)
        ->test(PostReactions::class, ['postId' => $post->id])
        ->assertSee('1 like', false);

    $this->assertDatabaseCount('post_likes', 1);
});

it('shows guests a sign-in link and blocks liking', function () {
    $post = reactionsPost('liked-post', ['title' => ['en' => 'Liked Post', 'bn' => '']]);

    Livewire::test(PostReactions::class, ['postId' => $post->id])
        ->assertSee('Sign in to like this post', false)
        ->assertSee('0 likes', false)
        ->call('toggleLike')
        ->assertStatus(403);

    expect(PostLike::count())->toBe(0);
});

it('deletes a post likes when the post is deleted', function () {
    $post = reactionsPost('doomed-post', ['title' => ['en' => 'Doomed Post', 'bn' => '']]);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PostReactions::class, ['postId' => $post->id])
        ->call('toggleLike');

    $this->assertDatabaseCount('post_likes', 1);

    $post->forceDelete();

    $this->assertDatabaseCount('post_likes', 0);
});
