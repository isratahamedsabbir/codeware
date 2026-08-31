<?php

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;

beforeEach(function () {
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
    Page::factory()->published()->create(['title' => ['en' => 'About Us', 'bn' => ''], 'slug' => 'about', 'sort_order' => 1]);
    Page::factory()->published()->create(['title' => ['en' => 'Contact Us', 'bn' => ''], 'slug' => 'contact', 'sort_order' => 2]);
    Page::factory()->published()->create(['title' => ['en' => 'FAQ', 'bn' => ''], 'slug' => 'faq', 'sort_order' => 3]);

    foreach ([['Home', '/', 0], ['About Us', '/about', 1], ['Contact Us', '/contact', 2], ['FAQ', '/faq', 3]] as [$label, $url, $order]) {
        MenuItem::create(['group' => 'frontend', 'label' => $label, 'url' => $url, 'sort_order' => $order, 'is_active' => true]);
    }
});

it('renders every standalone page across every theme', function () {
    foreach (['default', 'ecommerce', 'portfolio'] as $theme) {
        Setting::set('site_theme', $theme);

        foreach (['/', '/about', '/contact', '/faq'] as $path) {
            $this->get($path)->assertOk();
        }
    }
});

it('drives the default theme nav from the pages list, not the frontend menu', function () {
    Setting::set('site_theme', 'default');
    MenuItem::where('group', 'frontend')->update(['label' => 'RENAMED']);

    $this->get('/')->assertOk()->assertSee('About Us')->assertDontSee('RENAMED');
});

it('drives the portfolio and ecommerce theme nav from the frontend menu, not the pages list', function () {
    MenuItem::where('label', 'About Us')->update(['label' => 'Renamed Menu Item']);

    foreach (['portfolio', 'ecommerce'] as $theme) {
        Setting::set('site_theme', $theme);

        $this->get('/')->assertOk()->assertSee('Renamed Menu Item')->assertDontSee('About Us');
    }
});

it('reflects a frontend menu change in portfolio/ecommerce without touching the pages list', function () {
    MenuItem::create(['group' => 'frontend', 'label' => 'Extra Link', 'url' => '/faq', 'sort_order' => 99, 'is_active' => true]);

    Setting::set('site_theme', 'ecommerce');
    $this->get('/')->assertOk()->assertSee('Extra Link');

    Setting::set('site_theme', 'default');
    $this->get('/')->assertOk()->assertDontSee('Extra Link');
});

it('hides an inactive frontend menu item from portfolio/ecommerce nav', function () {
    MenuItem::where('label', 'FAQ')->update(['is_active' => false]);

    Setting::set('site_theme', 'portfolio');
    $this->get('/')->assertOk()->assertDontSee('>FAQ<', false);
});

it('highlights the current page as active in the frontend menu nav', function () {
    Setting::set('site_theme', 'portfolio');

    $this->get('/contact')->assertOk()->assertSeeInOrder(['Contact Us', 'is-active'], false);
});
