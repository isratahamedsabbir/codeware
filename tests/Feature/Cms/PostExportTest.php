<?php

use App\Models\Feature;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports posts as a downloadable csv with description columns', function () {
    $category = PostCategory::factory()->create(['name' => ['en' => 'News', 'bn' => 'সংবাদ']]);
    $post = Post::factory()->published()->create([
        'title' => ['en' => 'Hello World', 'bn' => 'হ্যালো বিশ্ব'],
        'description' => ['en' => 'English description', 'bn' => 'বাংলা বর্ণনা'],
        'category_id' => $category->id,
    ]);
    pairPageFor($post, 'post', 'hello-world', $this->admin->id);

    $response = $this->get(route('admin.posts.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $response->assertDownload();

    $csv = $response->streamedContent();

    expect($csv)->toContain('Title (EN)')
        ->and($csv)->toContain('Description (EN)')
        ->and($csv)->toContain('Description (BN)')
        ->and($csv)->toContain('Hello World')
        ->and($csv)->toContain('হ্যালো বিশ্ব')
        ->and($csv)->toContain('hello-world')
        ->and($csv)->toContain('News')
        ->and($csv)->toContain('English description')
        ->and($csv)->toContain('বাংলা বর্ণনা');
});

it('exports an import template containing only the header row', function () {
    Post::factory()->create(['title' => ['en' => 'Template Must Not Appear', 'bn' => '']]);

    $csv = $this->get(route('admin.posts.export', ['template' => 1]))->streamedContent();

    expect($csv)->toContain('Title (EN)')
        ->and($csv)->toContain('Description (EN)')
        ->and($csv)->toContain('Description (BN)')
        ->and($csv)->not->toContain('Template Must Not Appear')
        ->and(substr_count(trim($csv), "\n"))->toBe(0);
});

it('only includes posts matching the search filter', function () {
    Post::factory()->create(['title' => ['en' => 'Matching Post', 'bn' => '']]);
    Post::factory()->create(['title' => ['en' => 'Other Post', 'bn' => '']]);

    $csv = $this->get(route('admin.posts.export', ['search' => 'Matching']))->streamedContent();

    expect($csv)->toContain('Matching Post')
        ->and($csv)->not->toContain('Other Post');
});

it('only includes posts matching the status filter', function () {
    Post::factory()->active()->create(['title' => ['en' => 'Active Post', 'bn' => '']]);
    Post::factory()->inactive()->create(['title' => ['en' => 'Inactive Post', 'bn' => '']]);

    $csv = $this->get(route('admin.posts.export', ['status' => 'active']))->streamedContent();

    expect($csv)->toContain('Active Post')
        ->and($csv)->not->toContain('Inactive Post');
});

it('exports only the requested post ids', function () {
    $included = Post::factory()->create(['title' => ['en' => 'Selected Post', 'bn' => '']]);
    Post::factory()->create(['title' => ['en' => 'Other Post', 'bn' => '']]);

    $csv = $this->get(route('admin.posts.export', ['ids' => [$included->id], 'search' => 'nonexistent']))->streamedContent();

    expect($csv)->toContain('Selected Post')
        ->and($csv)->not->toContain('Other Post');
});

it('is blocked when the blog feature is disabled', function () {
    Feature::create(['key' => 'blog', 'label' => 'Blog', 'is_enabled' => false]);

    $this->get(route('admin.posts.export'))->assertNotFound();
});
