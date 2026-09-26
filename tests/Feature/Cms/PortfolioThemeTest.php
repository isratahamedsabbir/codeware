<?php

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Support\Frontend;
use Database\Seeders\PortfolioMenuSeeder;

beforeEach(function () {
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    Setting::set('site_theme', 'portfolio');
});

it('seeds a portfolio menu of its own, separate from the frontend menu', function () {
    $this->seed(PortfolioMenuSeeder::class);

    expect(Menu::where('slug', 'portfolio')->where('name', 'Portfolio')->exists())->toBeTrue()
        ->and(Frontend::portfolioMenuItems()->pluck('label')->all())
        ->toBe(['Home', 'Projects', 'Experience', 'Technology', 'Contact']);

    // Nothing seeded for the portfolio may leak into the ecommerce theme's nav.
    expect(MenuItem::where('group', 'portfolio')->exists())->toBeTrue()
        ->and(MenuItem::where('group', 'frontend')->exists())->toBeFalse();
});

it('is idempotent, so re-seeding neither duplicates items nor orphans the admin menu', function () {
    $this->seed(PortfolioMenuSeeder::class);
    $this->seed(PortfolioMenuSeeder::class);

    expect(MenuItem::where('group', 'portfolio')->count())->toBe(5)
        ->and(Menu::where('slug', 'portfolio')->count())->toBe(1);
});

it('keeps admin edits to a seeded item across a re-seed', function () {
    $this->seed(PortfolioMenuSeeder::class);
    MenuItem::where('group', 'portfolio')->where('label', 'Projects')->update(['label' => 'Work']);

    $this->seed(PortfolioMenuSeeder::class);

    expect(Frontend::portfolioMenuItems()->pluck('label')->all())->toBe(['Home', 'Work', 'Experience', 'Technology', 'Contact']);
});

it('points every seeded anchor at a section the one-pager actually renders', function () {
    $this->seed(PortfolioMenuSeeder::class);

    $html = $this->get('/')->assertOk()->getContent();

    foreach (Frontend::portfolioMenuItems() as $item) {
        expect($item->url)->toStartWith('#')
            ->and($html)->toContain('id="'.substr($item->url, 1).'"');
    }
});

it('renders the nav as section anchors wired to the scroll spy', function () {
    $this->seed(PortfolioMenuSeeder::class);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.url('/').'#projects"', false)
        ->assertSee('data-pf-nav-link="projects"', false)
        ->assertSee(asset('themes/portfolio/script.js'), false);
});

it('carries the same nav into the stacked mobile menu, since a phone cannot reach the sections otherwise', function () {
    $this->seed(PortfolioMenuSeeder::class);

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'data-pf-nav-link="projects"'))->toBe(2)
        ->and($html)->toContain('id="pf-mobile-nav"');
});

it('keeps its own light/dark toggle and stylesheet, independent of the app bundle', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-pf-theme-toggle', false)
        ->assertSee(asset('themes/portfolio/style.css'), false);
});
