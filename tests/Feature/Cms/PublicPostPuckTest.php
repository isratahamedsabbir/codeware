<?php

use App\Models\CmsSection;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;

it('public post show includes puck_data when present', function () {
    $post = Post::factory()->published()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'Title'], 'slug' => 'puck-post-show', 'status' => 'active',
        'puck_data' => [
            'root' => ['props' => []],
            'content' => [['type' => 'HeroSection', 'props' => []]],
        ],
    ]);

    $this->getJson("/api/v1/posts/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.page.puck_data.content.0.type', 'HeroSection');
});

it('public post listing also includes puck_data nested under page', function () {
    $post = Post::factory()->published()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'Title'], 'slug' => 'puck-post-listing', 'status' => 'active',
        'puck_data' => ['root' => ['props' => []], 'content' => [['type' => 'HeroSection', 'props' => []]]],
    ]);

    $this->getJson('/api/v1/posts')
        ->assertOk()
        ->assertJsonPath('data.0.page.puck_data.content.0.type', 'HeroSection');
});

it('includes the paired page\'s constant map and cms sections on a single post, but not on the listing', function () {
    $post = Post::factory()->published()->create();
    $page = Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'Title'], 'slug' => 'cms-post', 'status' => 'active',
        'constant' => [['key' => 'author_note', 'value' => 'Guest post']],
    ]);
    CmsSection::factory()->create([
        'page_id' => $page->id, 'name' => 'callout', 'status' => 'active',
        'constant' => [['key' => 'cta', 'value' => 'Subscribe']],
    ]);

    $this->getJson("/api/v1/posts/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.page.constant.author_note', 'Guest post')
        ->assertJsonPath('data.cms.0.name', 'callout')
        ->assertJsonPath('data.cms.0.constant.cta', 'Subscribe');

    $this->getJson('/api/v1/posts')
        ->assertOk()
        ->assertJsonMissingPath('data.0.cms');
});
