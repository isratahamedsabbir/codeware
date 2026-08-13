<?php

use Illuminate\Support\Facades\Schema;

it('creates all cms tables', function () {
    expect(Schema::hasTable('categories'))->toBeTrue();
    expect(Schema::hasTable('tags'))->toBeTrue();
    expect(Schema::hasTable('posts'))->toBeTrue();
    expect(Schema::hasTable('post_tag'))->toBeTrue();
    expect(Schema::hasTable('pages'))->toBeTrue();
    expect(Schema::hasTable('page_revisions'))->toBeTrue();
    expect(Schema::hasTable('media_library'))->toBeTrue();
    expect(Schema::hasTable('settings'))->toBeTrue();
});

it('posts table has required columns', function () {
    // seo_title/seo_description/og_image live on the paired `pages` row, not on posts
    // itself — see 'pages table has required columns' below.
    expect(Schema::hasColumns('posts', [
        'id', 'user_id', 'category_id', 'title', 'slug', 'content',
        'status', 'featured_image', 'published_at', 'reading_time', 'deleted_at',
    ]))->toBeTrue();
});

it('pages table has required columns', function () {
    expect(Schema::hasColumns('pages', [
        'id', 'user_id', 'title', 'slug', 'content',
        'status', 'template', 'sort_order',
        'seo_title', 'seo_description', 'deleted_at',
    ]))->toBeTrue();
});

it('settings table has is_public column', function () {
    expect(Schema::hasColumn('settings', 'is_public'))->toBeTrue();
});

it('users table has is_admin column', function () {
    expect(Schema::hasColumn('users', 'is_admin'))->toBeTrue();
});
