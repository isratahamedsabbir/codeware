<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
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

it('lays the listing out as a grid of cards rather than a column of rows', function () {
    // A portfolio is read by skimming, so a post has to be recognisable as a
    // piece of work from its card alone. One <article> per post, in a grid,
    // is the shape that makes the archive scannable.
    publishPost('Grid Card One', 'grid-one');
    publishPost('Grid Card Two', 'grid-two');

    $html = $this->get('/blog')->assertOk()->getContent();

    expect($html)->toContain('sm:grid-cols-2 lg:grid-cols-3')
        ->and(substr_count($html, '<article data-pf-reveal'))->toBe(2);
});

it('gives every card a single stretched link, so a post is one tab stop', function () {
    // A card links its title, and that link carries a ::after covering the whole
    // card. That is what makes the whole thing the hit target - including the
    // "Read more" in the corner, which is a span on purpose because a link
    // there would put the post in the tab order twice.
    //
    // The image does not link at all any more: it sits under the same stretched
    // link as everything else, and with the title right beside it its alt is
    // empty rather than the title repeated.
    $post = publishPost('A Linked Card', 'linked-card');
    $post->update(['featured_image' => '/storage/card.jpg']);

    $html = $this->get('/blog')->assertOk()->getContent();

    expect($html)->toContain('href="'.route('blog.post', 'linked-card').'"')
        ->and($html)->toContain("after:absolute after:inset-0 after:content-['']")
        ->and($html)->toContain('alt=""');

    // Exactly one post link in the card, and no separate link on the image or
    // on the "Read more" corner.
    expect(substr_count($html, 'href="'.route('blog.post', 'linked-card').'"'))->toBe(1)
        ->and($html)->not->toContain('tabindex="-1" aria-hidden="true"');
});

it('lifts the category filter above the stretched link so it stays clickable', function () {
    // The stretched ::after covers the card, so anything else inside it has to
    // sit above it. Without this the category is visible, styled as a link, and
    // cannot be clicked - the one place a visitor would try to filter from.
    $category = PostCategory::factory()->published()->create(['name' => ['en' => 'Guides', 'bn' => '']]);
    Page::factory()->published()->create([
        'title' => ['en' => 'Guides', 'bn' => ''],
        'slug' => 'guides',
        'type' => 'post_category',
        'category_id' => $category->id,
    ]);

    $post = publishPost('Has A Category', 'has-a-category');
    $post->update(['category_id' => $category->id]);

    expect($this->get('/blog')->assertOk()->getContent())
        ->toMatch('/uppercase tracking-wider[^"]*"/')
        ->and($this->get('/blog')->assertOk()->getContent())
        ->toContain('relative z-10');
});

it('counts what is listed, so a reader knows how much there is', function () {
    // Only when there is something to count: a zero on an empty archive reads
    // as a mistake rather than as an absence.
    publishPost('Counted Note', 'counted-note');

    $html = $this->get('/blog')->assertOk()->getContent();
    expect($html)->toContain('1 note published');
});

it('counts nothing rather than zero on an empty archive', function () {
    // The same line, on a page with no posts in it. A "0 notes published"
    // reads as a broken count rather than as an empty archive.
    expect($this->get('/blog')->assertOk()->getContent())
        ->not->toContain('note published')
        ->and($this->get('/blog')->assertOk()->getContent())
        ->toContain('Nothing published yet');
});

it('counts the notes inside a category, and says so', function () {
    // A filtered view is not the archive, so the wording changes with it -
    // "14 notes published" on a category page would be a number about a
    // different set of notes than the one on screen.
    $category = PostCategory::factory()->published()->create(['name' => ['en' => 'Guides', 'bn' => '']]);
    Page::factory()->published()->create([
        'title' => ['en' => 'Guides', 'bn' => ''],
        'slug' => 'guides',
        'type' => 'post_category',
        'category_id' => $category->id,
    ]);

    $inCategory = publishPost('A Guide', 'a-guide');
    $inCategory->update(['category_id' => $category->id]);
    publishPost('Some News', 'some-news');

    $html = $this->get('/blog?category=guides')->assertOk()->getContent();

    expect($html)->toContain('1 note in this category')
        ->and($html)->toContain('A Guide')
        ->and($html)->not->toContain('Some News');
});

it('shows how many notes each category holds next to its filter', function () {
    // The controller already counts the active posts per category for this
    // nav, so the count turns a row of filters into a map of where the
    // writing actually is. A filter that hides an empty category is worse
    // than one that says the category is empty.
    $category = PostCategory::factory()->published()->create(['name' => ['en' => 'Guides', 'bn' => '']]);
    Page::factory()->published()->create([
        'title' => ['en' => 'Guides', 'bn' => ''],
        'slug' => 'guides',
        'type' => 'post_category',
        'category_id' => $category->id,
    ]);

    $post = publishPost('Counted In Guides', 'counted-in-guides');
    $post->update(['category_id' => $category->id]);

    $html = $this->get('/blog')->assertOk()->getContent();

    // A draft in the same category must not be counted: the filter navigates
    // to published posts only, so a number that included drafts would promise
    // a page with a post on it that is not there.
    $draft = Post::factory()->draft()->create([
        'title' => ['en' => 'An Unwritten Guide', 'bn' => ''],
        'category_id' => $category->id,
    ]);
    Page::factory()->published()->create([
        'title' => ['en' => 'An Unwritten Guide', 'bn' => ''],
        'slug' => 'an-unwritten-guide',
        'type' => 'post',
        'post_id' => $draft->id,
    ]);

    expect($this->get('/blog')->assertOk()->getContent())
        ->toMatch('/opacity-60">1<\/span>/')
        ->and($this->get('/blog')->assertOk()->getContent())
        ->not->toMatch('/opacity-60">2<\/span>/');
});

it('leaves a category-less post with no dangling separator', function () {
    // The dot between the category and the date belongs to the pair. A post
    // with no category would otherwise open its meta row on a stray "·".
    $post = publishPost('No Category Here', 'no-category-here');

    $html = $this->get('/blog')->assertOk()->getContent();

    expect($post->category_id)->toBeNull()
        ->and($html)->toContain('<time datetime=')
        ->and($html)->not->toMatch('/tracking-wider[^>]*>\s*<\/a>\s*<span[^>]*>&middot;<\/span>\s*<time/');
});

it('dates each card with a machine-readable time', function () {
    // The visible date is formatted for people; the datetime attribute is what
    // a machine reads, and it is the only part of it that is a date at all.
    publishPost('Dated Note', 'dated-note', publishedAt: now()->startOfYear());

    $html = $this->get('/blog')->assertOk()->getContent();

    expect($html)->toMatch('/<time datetime="\d{4}-\d{2}-\d{2}"/');
});

it('pages the archive in the theme rather than in the framework', function () {
    // Laravel's default pager is unstyled blue underlined text, which is the
    // one thing on this page that cannot belong to a portfolio. The theme
    // builds its own, so the pager looks like the rest of the page - and
    // carries the category filter across, since a page of a filtered archive
    // that dropped the filter would be a different set of notes by page two.
    foreach (range(1, 12) as $n) {
        publishPost("Paged Note {$n}", "paged-note-{$n}");
    }

    $first = $this->get('/blog')->assertOk()->getContent();

    expect($first)->toContain('aria-label="Blog pagination"')
        ->and($first)->not->toContain('Laravel Pagination')
        ->and($first)->toContain('aria-current="page">1<');

    $second = $this->get('/blog?page=2')->assertOk()->getContent();

    expect($second)->toContain('aria-current="page">2<')
        ->and($second)->toContain('rel="prev"')
        ->and($second)->not->toContain('rel="next"');
});

it('leaves out a pager when there is only one page of notes', function () {
    // A pager on a single page of results is a control for nothing.
    publishPost('The Only Note', 'the-only-note');

    expect($this->get('/blog')->assertOk()->getContent())
        ->not->toContain('aria-label="Blog pagination"');
});

it('invites the reader back to the work when nothing is published', function () {
    // A portfolio with the blog switched on and no posts yet is a normal state,
    // not an error. A dashed empty box reads as a bug; a line about what will
    // appear there, and a way back to the work, does not.
    $html = $this->get('/blog')->assertOk()->getContent();

    expect($html)->toContain('Nothing published yet')
        ->and($html)->toContain('Back to the work')
        ->and($html)->toContain('href="'.route('home').'"');
});
