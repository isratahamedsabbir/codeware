<?php

use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Setting;
use App\Models\User;

it('blog category auto-generates slug from name', function () {
    $category = PostCategory::factory()->create(['name' => ['en' => 'My Test Category', 'bn' => ''], 'slug' => '']);
    expect($category->fresh()->slug)->toBe('my-test-category');
});

it('post auto-generates slug from title', function () {
    $post = Post::factory()->create(['title' => ['en' => 'My First Post', 'bn' => ''], 'slug' => '']);
    expect($post->fresh()->slug)->toBe('my-first-post');
});

it('post published scope filters correctly', function () {
    Post::factory()->published()->create();
    Post::factory()->draft()->create();
    expect(Post::published()->count())->toBe(1);
    expect(Post::draft()->count())->toBe(1);
});

it('page auto-generates slug from title', function () {
    $page = Page::factory()->create(['title' => ['en' => 'About Us', 'bn' => ''], 'slug' => '']);
    expect($page->fresh()->slug)->toBe('about-us');
});

it('page published scope filters correctly', function () {
    Page::factory()->published()->create();
    Page::factory()->draft()->create();
    expect(Page::published()->count())->toBe(1);
    expect(Page::draft()->count())->toBe(1);
});

it('setting get and set work correctly', function () {
    Setting::set('site_name', 'Agrosal');
    expect(Setting::get('site_name'))->toBe('Agrosal');
});

it('setting get returns default when key missing', function () {
    expect(Setting::get('nonexistent_key', 'default'))->toBe('default');
});

it('media library url accessor returns storage url', function () {
    $media = MediaLibrary::factory()->create(['path' => 'media/test.jpg', 'disk' => 'public']);
    expect($media->url)->toContain('test.jpg');
});

it('post has correct relationships', function () {
    $user = User::factory()->create();
    $category = PostCategory::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

    expect($post->user->id)->toBe($user->id);
    expect($post->category->id)->toBe($category->id);
});
