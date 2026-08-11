<?php

use App\Models\BlogCategory;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;

it('returns published posts only', function () {
    Post::factory()->published()->create(['title' => ['en' => 'Live Post', 'bn' => '']]);
    Post::factory()->draft()->create(['title' => ['en' => 'Hidden Draft', 'bn' => '']]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertOk()
        ->assertJsonPath('data.0.title', 'Live Post')
        ->assertJsonCount(1, 'data');
});

it('returns paginated posts with meta', function () {
    Post::factory()->count(3)->published()->create();

    $response = $this->getJson('/api/v1/posts?per_page=2');

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3);
});

it('returns translated title with locale param', function () {
    Post::factory()->published()->create([
        'title' => ['en' => 'English Title', 'bn' => 'বাংলা শিরোনাম'],
    ]);

    $response = $this->getJson('/api/v1/posts?locale=bn');

    $response->assertOk()
        ->assertJsonPath('data.0.title', 'বাংলা শিরোনাম');
});

it('returns a single published post by slug', function () {
    $post = Post::factory()->published()->create([
        'title' => ['en' => 'Single Post', 'bn' => ''],
    ]);

    $response = $this->getJson("/api/v1/posts/{$post->slug}");

    $response->assertOk()
        ->assertJsonPath('data.slug', $post->slug)
        ->assertJsonStructure(['data' => ['id', 'slug', 'title', 'content']]);
});

it('returns 404 for draft post slug on public endpoint', function () {
    $post = Post::factory()->draft()->create();

    $this->getJson("/api/v1/posts/{$post->slug}")->assertNotFound();
});

it('returns published pages', function () {
    Page::factory()->published()->create(['title' => ['en' => 'About Us', 'bn' => '']]);

    $response = $this->getJson('/api/v1/pages');

    $response->assertOk()
        ->assertJsonPath('data.0.title', 'About Us');
});

it('returns a single published page by slug', function () {
    $page = Page::factory()->published()->create([
        'title' => ['en' => 'Contact', 'bn' => ''],
    ]);

    $response = $this->getJson("/api/v1/pages/{$page->slug}");

    $response->assertOk()
        ->assertJsonPath('data.slug', $page->slug)
        ->assertJsonStructure(['data' => ['id', 'slug', 'title', 'content']]);
});

it('returns only public settings', function () {
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Agrosal', 'is_public' => true]);
    Setting::factory()->create(['key' => 'secret_key', 'value' => 'shh', 'is_public' => false]);

    $response = $this->getJson('/api/v1/settings/public');

    $response->assertOk()
        ->assertJsonPath('data.site_name', 'Agrosal')
        ->assertJsonMissingPath('data.secret_key');
});

it('returns layout header and footer', function () {
    Setting::set('header_content', '{"blocks":[]}');
    Setting::set('footer_content', '{"blocks":[]}');

    $response = $this->getJson('/api/v1/layout');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['header', 'footer']]);
});

it('filters posts by category slug', function () {
    $cat = BlogCategory::factory()->create(['slug' => 'news']);
    Post::factory()->published()->create(['category_id' => $cat->id, 'title' => ['en' => 'News Post', 'bn' => '']]);
    Post::factory()->published()->create(['title' => ['en' => 'Other Post', 'bn' => '']]);

    $response = $this->getJson('/api/v1/posts?category=news');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('filters posts by tag slug', function () {
    $tag = Tag::factory()->create(['slug' => 'featured']);
    $post = Post::factory()->published()->create(['title' => ['en' => 'Featured Post', 'bn' => '']]);
    $post->tags()->attach($tag);
    Post::factory()->published()->create(['title' => ['en' => 'Normal Post', 'bn' => '']]);

    $response = $this->getJson('/api/v1/posts?tag=featured');

    $response->assertOk()->assertJsonCount(1, 'data');
});
