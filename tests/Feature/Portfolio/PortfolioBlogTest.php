<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Support\Themes;
use Database\Seeders\PortfolioMenuSeeder;

/*
 * The portfolio theme's blog: the listing, the single post, and the teaser that
 * sits above the footer on the one-pager.
 *
 * The plumbing underneath is the ecommerce theme's, unchanged — the same routes,
 * the same FrontendController methods, the same Post rows. What is new is that
 * the portfolio theme owns templates for those route names, so the 'theme' guard
 * lets /blog answer here instead of 404ing. That pairing is the thing worth
 * pinning down: a route registered without a template resolves and then 500s.
 */

beforeEach(function () {
    Setting::set('site_theme', 'portfolio');
});

/**
 * A published post with the paired Page that owns its slug.
 *
 * Order matters, and the direction of the link is the thing that is easy to get
 * wrong: `posts` has no `page_id`. The foreign key is `pages.post_id`, and
 * Post::page() is a hasOne(Page, 'post_id') narrowed to type = 'post' - so the
 * Post is created first and the Page is attached to it. Post::slug() is a
 * virtual accessor reading $this->page?->slug, which is why a post with no
 * paired page has no URL to be read at.
 */
function publishPost(string $title, string $slug, string $description = 'Some words about the work.', ?string $publishedAt = null): Post
{
    $post = Post::factory()->published()->create([
        'title' => ['en' => $title, 'bn' => ''],
        'description' => ['en' => $description, 'bn' => ''],
        'published_at' => $publishedAt ?? now(),
    ]);

    Page::factory()->published()->create([
        'title' => ['en' => $title, 'bn' => ''],
        'slug' => $slug,
        'type' => 'post',
        'post_id' => $post->id,
    ]);

    return $post;
}

it('owns the blog templates, so the theme guard lets the routes answer', function () {
    // The single definition of "does this theme have this page?" is whether it
    // ships the template (App\Support\Themes::hasTemplateFor). The middleware
    // asks it before the controller runs, so a route with no template is a 404
    // and a template with no route is unreachable.
    expect(Themes::hasTemplateFor('portfolio', 'blog'))->toBeTrue()
        ->and(Themes::hasTemplateFor('portfolio', 'post'))->toBeTrue()
        ->and(Themes::activeThemeCanRender('blog'))->toBeTrue()
        ->and(Themes::activeThemeCanRender('blog.post'))->toBeTrue();
});

it('serves the listing and the single post', function () {
    $post = publishPost('Shipping the First Release', 'shipping-the-first-release', 'What broke and what I would do differently.');

    $this->get('/blog')
        ->assertOk()
        ->assertSee('Shipping the First Release')
        ->assertSee('What broke and what I would do differently.');

    $this->get('/blog/shipping-the-first-release')
        ->assertOk()
        ->assertSee('Shipping the First Release')
        ->assertSee('What broke and what I would do differently.');

    expect($post->slug)->toBe('shipping-the-first-release');
});

it('teases the newest posts above the footer, and links through to them', function () {
    // Spaced a day apart on purpose. Three posts created in the same second all
    // share a published_at, and the query's tiebreak is then id desc - which puts
    // the last one created first, so a test that relies on creation order is
    // really testing the tiebreak and not the date sort the teaser advertises.
    publishPost('Newest Note', 'newest-note', 'Newest words.', now()->subDay());
    publishPost('Middle Note', 'middle-note', 'Middle words.', now()->subWeek());
    publishPost('Oldest Note', 'oldest-note', 'Oldest words.', now()->subMonth());

    $html = $this->get('/')->assertOk()->getContent();

    // Before the footer, not after it and not somewhere in the middle: a blog
    // that renders below the footer is a section no visitor reaches. The footer
    // is matched on its opening tag's own classes, since there is no id on it.
    $writing = strpos($html, 'id="writing"');
    $footer = strpos($html, '<footer class="border-t border-(--pf-border) px-6 pt-16 pb-8">');

    expect($writing)->not->toBeFalse()
        ->and($footer)->not->toBeFalse()
        ->and($writing)->toBeLessThan($footer);

    // The three most recent, newest first.
    expect(strpos($html, 'Newest Note'))->toBeLessThan(strpos($html, 'Middle Note'))
        ->and(strpos($html, 'Middle Note'))->toBeLessThan(strpos($html, 'Oldest Note'));

    $this->get('/')->assertOk()->assertSee('href="'.route('blog.post', 'newest-note').'"', false);
});

it('shows at most three posts in the teaser, with the archive as the only way to the rest', function () {
    foreach (range(1, 5) as $n) {
        publishPost("Note {$n}", "note-{$n}");
    }

    $html = $this->get('/')->assertOk()->getContent();

    // Three cards, and a link out — the teaser is a sample, so without the link
    // to the full list the two older posts would be unreachable from the homepage.
    $postLinkPrefix = 'href="'.route('blog.post', 'note-');

    expect(substr_count($html, $postLinkPrefix))->toBe(3)
        ->and($html)->toContain('href="'.route('blog').'"');
});

it('prints no writing section at all when nothing is published', function () {
    // Nothing links to #writing, so an empty shelf would advertise a gap rather
    // than a portfolio that has simply not written anything yet.
    expect($this->get('/')->assertOk()->getContent())->not->toContain('id="writing"');
});

it('still hides a draft, so an unpublished post is not readable by URL', function () {
    $post = Post::factory()->draft()->create(['title' => ['en' => 'A Sealed Draft', 'bn' => '']]);

    // The page is published and the slug resolves, so this is specifically about
    // the post's own status being what keeps it out - not about a missing page.
    Page::factory()->published()->create([
        'title' => ['en' => 'A Sealed Draft', 'bn' => ''],
        'slug' => 'a-sealed-draft',
        'type' => 'post',
        'post_id' => $post->id,
    ]);

    expect($this->get('/')->assertOk()->getContent())->not->toContain('A Sealed Draft')
        ->and($this->get('/blog/a-sealed-draft'))->assertNotFound();
});

it('404s on a slug no published post owns', function () {
    $this->get('/blog/nothing-here')->assertNotFound();
});

it('leaves /blog to the theme that owns it when another theme is active', function () {
    // The guard is keyed on the route name, so the ecommerce site keeps its own
    // /blog and this theme is simply not consulted.
    Setting::set('site_theme', 'ecommerce');

    expect(Themes::active())->toBe('ecommerce')
        ->and(Themes::hasTemplateFor('ecommerce', 'blog'))->toBeTrue()
        ->and(Themes::hasTemplateFor('ecommerce', 'post'))->toBeTrue();
});

it('reaches the blog from the portfolio nav as a real page', function () {
    $this->seed(PortfolioMenuSeeder::class);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.url('/blog').'"', false);
});

it('skips a post whose paired page is missing, since it has no slug to be found by', function () {
    // Post::slug() reads $this->page?->slug, so a post with no paired page is
    // unreachable and the teaser has to leave it out rather than print a card
    // with an empty link. The teaser query requires the page for this reason.
    Post::factory()->published()->create([
        'title' => ['en' => 'An Orphan Post', 'bn' => ''],
    ]);

    expect($this->get('/')->assertOk()->getContent())->not->toContain('An Orphan Post');
});
