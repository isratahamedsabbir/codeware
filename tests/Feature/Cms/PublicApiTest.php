<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

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
    pairPageFor($post, 'post', 'single-post', $this->admin->id);

    $response = $this->getJson("/api/v1/posts/{$post->slug}");

    $response->assertOk()
        ->assertJsonPath('data.slug', $post->slug)
        ->assertJsonStructure(['data' => ['id', 'slug', 'title', 'content']]);
});

it('returns 404 for draft post slug on public endpoint', function () {
    $post = Post::factory()->draft()->create();
    pairPageFor($post, 'post', 'draft-post', $this->admin->id);

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
        ->assertJsonStructure(['data' => ['id', 'slug', 'title', 'content', 'puck_data', 'meta_data' => [
            'seo_title', 'seo_description', 'og_title', 'og_description', 'og_image', 'twitter_title', 'twitter_description', 'twitter_image', 'no_index', 'no_follow',
        ]]]);
});

it('includes puck_data in both the pages listing and a single page', function () {
    $puckData = ['root' => ['props' => []], 'content' => [['type' => 'Hero']]];
    $page = Page::factory()->published()->create(['puck_data' => $puckData]);

    $this->getJson('/api/v1/pages')
        ->assertOk()
        ->assertJsonPath('data.0.puck_data', $puckData);

    $this->getJson("/api/v1/pages/{$page->slug}")
        ->assertOk()
        ->assertJsonPath('data.puck_data', $puckData);
});

it('returns public settings grouped by category', function () {
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Codeware', 'group' => 'general', 'is_public' => true]);
    Setting::factory()->create(['key' => 'secret_key', 'value' => 'shh', 'group' => 'general', 'is_public' => false]);
    Setting::factory()->create(['key' => 'site_icon', 'value' => '/icon.png', 'group' => 'images', 'is_public' => true]);
    Setting::factory()->create(['key' => 'pagination_per_page', 'value' => '10', 'group' => 'pagination', 'is_public' => true]);
    Setting::factory()->create(['key' => 'app_locale', 'value' => 'en', 'group' => 'localization', 'is_public' => true]);
    Setting::factory()->create(['key' => 'currency_code', 'value' => 'BDT', 'group' => 'currency', 'is_public' => true]);
    Setting::factory()->create(['key' => 'primary_color', 'value' => '#1e7bc4', 'group' => 'colors', 'is_public' => true]);
    Setting::factory()->create(['key' => 'secondary_color', 'value' => '#7cc242', 'group' => 'colors', 'is_public' => true]);
    Setting::factory()->create(['key' => 'seo_meta_title', 'value' => 'Codeware | Home', 'group' => 'seo', 'is_public' => true]);

    $response = $this->getJson('/api/v1/settings/public');

    $response->assertOk()
        ->assertJsonPath('data.general.site_name', 'Codeware')
        ->assertJsonMissingPath('data.general.secret_key')
        ->assertJsonPath('data.images.site_icon', '/icon.png')
        ->assertJsonPath('data.pagination.pagination_per_page', '10')
        ->assertJsonPath('data.localization.app_locale', 'en')
        ->assertJsonPath('data.currency.currency_code', 'BDT')
        ->assertJsonPath('data.theme.primary_color', '#1e7bc4')
        ->assertJsonPath('data.theme.secondary_color', '#7cc242')
        ->assertJsonPath('data.seo.seo_meta_title', 'Codeware | Home')
        ->assertJsonStructure(['data' => ['general', 'images', 'pagination', 'localization', 'currency', 'theme', 'constant', 'seo', 'social_links']]);
});

it('decodes seo_canonical_urls into an array within the seo group', function () {
    Setting::factory()->create([
        'key' => 'seo_canonical_urls', 'value' => json_encode(['/old-path' => '/new-path']),
        'group' => 'seo', 'is_public' => true,
    ]);

    $this->getJson('/api/v1/settings/public')
        ->assertOk()
        ->assertJsonPath('data.seo.seo_canonical_urls', ['/old-path' => '/new-path']);
});

it('decodes constants into a flat key => value map', function () {
    Setting::set('constants', json_encode([
        ['key' => 'support_email', 'type' => 'textarea', 'value' => 'support@example.com'],
    ]));

    $this->getJson('/api/v1/settings/public')
        ->assertOk()
        ->assertJsonPath('data.constant.support_email', 'support@example.com');
});

it('includes social links from the social_links table', function () {
    SocialLink::create(['platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com/codeware']);
    SocialLink::create(['platform' => 'twitter', 'label' => 'Twitter / X', 'url' => '']);

    $this->getJson('/api/v1/settings/public')
        ->assertOk()
        ->assertJsonPath('data.social_links.facebook', 'https://facebook.com/codeware')
        ->assertJsonMissingPath('data.social_links.twitter');
});

it('returns layout header and footer', function () {
    Setting::set('header_content', '{"blocks":[]}');
    Setting::set('footer_content', '{"blocks":[]}');

    $response = $this->getJson('/api/v1/layout');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['header', 'footer']]);
});

it('filters posts by category slug', function () {
    $cat = PostCategory::factory()->create();
    pairPageFor($cat, 'post_category', 'news', $this->admin->id);
    Post::factory()->published()->create(['category_id' => $cat->id, 'title' => ['en' => 'News Post', 'bn' => '']]);
    Post::factory()->published()->create(['title' => ['en' => 'Other Post', 'bn' => '']]);

    $response = $this->getJson('/api/v1/posts?category=news');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('returns tags on published posts', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Laravel', 'bn' => 'লারাভেল']]);
    $post = Post::factory()->published()->create(['title' => ['en' => 'Tagged Post', 'bn' => '']]);
    $post->tags()->attach($tag);

    $response = $this->getJson('/api/v1/posts?locale=bn');

    $response->assertOk()
        ->assertJsonPath('data.0.tags.0.name', 'লারাভেল')
        ->assertJsonPath('data.0.tags.0.slug', $tag->slug);
});
