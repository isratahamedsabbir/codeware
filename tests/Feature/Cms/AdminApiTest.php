<?php

use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('rejects unauthenticated requests to admin api', function () {
    $this->getJson('/api/v1/admin/posts')->assertUnauthorized();
});

it('rejects non-admin authenticated users from admin api', function () {
    $regularUser = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($regularUser);

    $this->getJson('/api/v1/admin/posts')->assertForbidden();
});

it('admin can list all posts including drafts via api', function () {
    Sanctum::actingAs($this->admin);

    Post::factory()->active()->create(['title' => ['en' => 'Active', 'bn' => '']]);
    Post::factory()->inactive()->create(['title' => ['en' => 'Inactive', 'bn' => '']]);

    $response = $this->getJson('/api/v1/admin/posts');

    $response->assertOk()->assertJsonPath('meta.total', 2);
});

it('admin can fetch a single post by id via api', function () {
    Sanctum::actingAs($this->admin);
    $post = Post::factory()->create(['title' => ['en' => 'Detail Post', 'bn' => '']]);

    $response = $this->getJson("/api/v1/admin/posts/{$post->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $post->id)
        ->assertJsonStructure(['data' => ['content']]);
});

it('admin can update post content via api', function () {
    Sanctum::actingAs($this->admin);
    $post = Post::factory()->create();

    $response = $this->putJson("/api/v1/admin/posts/{$post->id}", [
        'content' => ['en' => '{"blocks":[{"type":"paragraph"}]}', 'bn' => ''],
    ]);

    $response->assertOk()->assertJsonPath('data.id', $post->id);
    expect(Post::find($post->id)->getTranslation('content', 'en'))->toBe('{"blocks":[{"type":"paragraph"}]}');
});

it('admin can list all pages via api', function () {
    Sanctum::actingAs($this->admin);
    Page::factory()->count(2)->create(['status' => 'active']);

    $response = $this->getJson('/api/v1/admin/pages');

    $response->assertOk()->assertJsonCount(2, 'data');
});

it('admin can update page content via api', function () {
    Sanctum::actingAs($this->admin);
    $page = Page::factory()->create();

    $response = $this->putJson("/api/v1/admin/pages/{$page->id}", [
        'content' => ['en' => '{"root":{"props":{}}}', 'bn' => ''],
    ]);

    $response->assertOk()->assertJsonPath('data.id', $page->id);
});

it('admin can list media via api', function () {
    Sanctum::actingAs($this->admin);
    MediaLibrary::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/media');

    $response->assertOk()->assertJsonPath('meta.total', 3);
});

it('admin can update layout via api', function () {
    Sanctum::actingAs($this->admin);

    $response = $this->putJson('/api/v1/admin/layout', [
        'type' => 'header',
        'content' => ['root' => ['props' => []]],
    ]);

    $response->assertOk()->assertJsonPath('data.key', 'header_content');
});

it('admin layout update rejects invalid type', function () {
    Sanctum::actingAs($this->admin);

    $this->putJson('/api/v1/admin/layout', [
        'type' => 'sidebar',
        'content' => [],
    ])->assertUnprocessable();
});
