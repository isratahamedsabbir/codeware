<?php

use App\Models\Feature;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

function storefrontPost(string $slug, array $attributes = []): Post
{
    $post = Post::factory()->published()->create($attributes + ['user_id' => User::factory()->create()->id]);

    pairPageFor($post, 'post', $slug, User::factory()->create()->id);

    return $post;
}

it('lists only published posts on the blog page', function () {
    $visible = storefrontPost('visible-post', ['title' => ['en' => 'Visible Post', 'bn' => '']]);
    $hidden = Post::factory()->draft()->create(['title' => ['en' => 'Hidden Post', 'bn' => '']]);
    pairPageFor($hidden, 'post', 'hidden-post', $this->admin->id);

    get('/blog')
        ->assertOk()
        ->assertSee('Visible Post', false)
        ->assertSee('/blog/visible-post')
        ->assertDontSee('Hidden Post');
});

it('shows a single published post and hides drafts', function () {
    $visible = storefrontPost('single-post', [
        'title' => ['en' => 'Single Post', 'bn' => ''],
        'description' => ['en' => 'A lead paragraph.', 'bn' => ''],
        'featured_image' => '/storage/demo.jpg',
    ]);

    get('/blog/single-post')
        ->assertOk()
        ->assertSee('Single Post', false)
        ->assertSee('A lead paragraph.')
        ->assertSee('/storage/demo.jpg')
        ->assertDontSee('Read more');

    $hidden = Post::factory()->draft()->create(['title' => ['en' => 'Hidden Post', 'bn' => '']]);
    pairPageFor($hidden, 'post', 'hidden-post', $this->admin->id);

    get('/blog/hidden-post')->assertNotFound();
});

it('renders the post description as rich HTML when it contains markup', function () {
    storefrontPost('html-post', [
        'title' => ['en' => 'HTML Post', 'bn' => ''],
        'description' => ['en' => '<h2>Rich lead</h2><p>Body with <strong>bold</strong>.</p>', 'bn' => ''],
    ]);

    $html = get('/blog/html-post')->assertOk()->getContent();

    expect($html)->toContain('<h2>Rich lead</h2>')
        ->and($html)->toContain('<strong>bold</strong>');

    $listing = get('/blog')->assertOk()->getContent();

    expect($listing)->toContain('Rich lead');
});

it('caps the blog listing description at 1000 characters instead of clamping it', function () {
    $longText = str_repeat('Word ', 400);
    storefrontPost('long-post', [
        'title' => ['en' => 'Long Post', 'bn' => ''],
        'description' => ['en' => '<p>'.$longText.'</p>', 'bn' => ''],
    ]);

    $listing = get('/blog')->assertOk()->getContent();

    $plain = trim(html_entity_decode(strip_tags('<p>'.$longText.'</p>')));

    expect($listing)->toContain('Long Post')
        ->and($listing)->not->toContain($plain)
        ->and($listing)->toContain(Str::limit($plain, 1000));
});

it('filters the blog by category', function () {
    $category = PostCategory::factory()->published()->create(['name' => ['en' => 'Guides', 'bn' => '']]);
    $page = pairPageFor($category, 'post_category', 'guides', $this->admin->id);

    $inCategory = storefrontPost('guide-post', ['title' => ['en' => 'A Guide', 'bn' => ''], 'category_id' => $category->id]);
    storefrontPost('news-post', ['title' => ['en' => 'Some News', 'bn' => '']]);

    get('/blog')
        ->assertOk()
        ->assertSee('Guides', false)
        ->assertSee('/blog?category=guides');

    get('/blog?category=guides')
        ->assertOk()
        ->assertSee('A Guide', false)
        ->assertDontSee('Some News');
});

it('returns 404 for the blog when the feature is disabled', function () {
    Feature::create(['key' => 'blog', 'label' => 'Blog (Posts)', 'is_enabled' => false]);

    storefrontPost('hidden-by-feature', ['title' => ['en' => 'Off Post', 'bn' => '']]);

    get('/blog')->assertNotFound();
    get('/blog/hidden-by-feature')->assertNotFound();
});
