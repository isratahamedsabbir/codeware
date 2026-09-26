<?php

use App\Models\Category;
use App\Models\Language;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
    Page::factory()->published()->create(['title' => ['en' => 'About Us', 'bn' => ''], 'slug' => 'about', 'sort_order' => 1]);
    Page::factory()->published()->create(['title' => ['en' => 'Contact Us', 'bn' => ''], 'slug' => 'contact', 'sort_order' => 2]);
    Page::factory()->published()->create(['title' => ['en' => 'FAQ', 'bn' => ''], 'slug' => 'faq', 'sort_order' => 3]);

    foreach ([['Home', '/', 0], ['About Us', '/about', 1], ['Contact Us', '/contact', 2], ['FAQ', '/faq', 3]] as [$label, $url, $order]) {
        MenuItem::create(['group' => 'frontend', 'label' => $label, 'url' => $url, 'sort_order' => $order, 'is_active' => true]);
    }

    // The portfolio theme reads its own menu, and its items are section anchors
    // on the one-pager rather than routes (see PortfolioMenuSeeder).
    foreach ([['Home', '#home', 0], ['Projects', '#projects', 1]] as [$label, $url, $order]) {
        MenuItem::create(['group' => 'portfolio', 'label' => $label, 'url' => $url, 'sort_order' => $order, 'is_active' => true]);
    }
});

it('renders every standalone page on the themes that ship a page template', function () {
    foreach (['default', 'ecommerce'] as $theme) {
        Setting::set('site_theme', $theme);

        foreach (['/', '/about', '/contact', '/faq'] as $path) {
            $this->get($path)->assertOk();
        }
    }
});

it('404s every standalone page on the portfolio theme, which is a one-pager', function () {
    // The portfolio is a single page: its contact is the #contact section on the
    // one-pager, not a /contact page. It ships no page.blade.php, so the rows
    // below exist in the database but have nowhere to render on this theme —
    // which is exactly the 404 the theme-scoping rule is for.
    Setting::set('site_theme', 'portfolio');

    $this->get('/')->assertOk();

    foreach (['/about', '/contact', '/faq'] as $path) {
        $this->get($path)->assertNotFound();
    }
});

it('drives the default theme nav from the pages list, not the frontend menu', function () {
    Setting::set('site_theme', 'default');
    MenuItem::where('group', 'frontend')->update(['label' => 'RENAMED']);

    // The default theme's homepage is a bare login/dashboard card with no nav
    // — the pages-list-driven nav lives on its other pages (about/contact/faq).
    $this->get('/about')->assertOk()->assertSee('About Us')->assertDontSee('RENAMED');
});

it('drives the ecommerce theme nav from the frontend menu, not the pages list', function () {
    MenuItem::where('label', 'About Us')->update(['label' => 'Renamed Menu Item']);

    Setting::set('site_theme', 'ecommerce');

    $this->get('/')->assertOk()->assertSee('Renamed Menu Item')->assertDontSee('About Us');
});

it('drives the portfolio theme nav from the portfolio menu, not the frontend menu', function () {
    MenuItem::create(['group' => 'portfolio', 'label' => 'Work', 'url' => '#projects', 'sort_order' => 0, 'is_active' => true]);
    MenuItem::where('group', 'frontend')->update(['label' => 'Renamed Menu Item']);

    Setting::set('site_theme', 'portfolio');

    $this->get('/')
        ->assertOk()
        ->assertSee('Work')
        ->assertSee('data-pf-nav-link="projects"', false)
        ->assertDontSee('Renamed Menu Item');
});

it('reflects a frontend menu change in ecommerce without touching the pages list', function () {
    MenuItem::create(['group' => 'frontend', 'label' => 'Extra Link', 'url' => '/faq', 'sort_order' => 99, 'is_active' => true]);

    Setting::set('site_theme', 'ecommerce');
    $this->get('/')->assertOk()->assertSee('Extra Link');

    Setting::set('site_theme', 'default');
    $this->get('/')->assertOk()->assertDontSee('Extra Link');
});

it('hides an inactive frontend menu item from the ecommerce nav', function () {
    MenuItem::where('label', 'FAQ')->update(['is_active' => false]);

    Setting::set('site_theme', 'ecommerce');
    $this->get('/')->assertOk()->assertDontSee('>FAQ<', false);
});

it('hides an inactive portfolio menu item from the portfolio nav', function () {
    MenuItem::create(['group' => 'portfolio', 'label' => 'Work', 'url' => '#projects', 'sort_order' => 0, 'is_active' => true]);
    MenuItem::create(['group' => 'portfolio', 'label' => 'Retired', 'url' => '#technology', 'sort_order' => 1, 'is_active' => false]);

    Setting::set('site_theme', 'portfolio');
    $this->get('/')->assertOk()->assertSee('Work')->assertDontSee('Retired');
});

it('resolves a portfolio section anchor to the site root', function () {
    Setting::set('site_theme', 'portfolio');

    // Anchored to the root rather than left as a bare "#projects", which would
    // resolve against whatever page you happened to be on and find nothing.
    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.url('/').'#projects"', false)
        ->assertSee('data-pf-nav-link="projects"', false);
});

it('drops a portfolio nav path item the one-pager theme cannot render', function () {
    MenuItem::create(['group' => 'portfolio', 'label' => 'Resume', 'url' => '/about', 'sort_order' => 5, 'is_active' => true]);

    Setting::set('site_theme', 'portfolio');

    $html = $this->get('/')->assertOk()->getContent();

    // /about is a published page, but this theme ships no page.blade.php, so a
    // nav link to it would be a dead end — it goes instead of being advertised.
    expect($html)->not->toContain('Resume');

    // An anchor can never be marked active server-side either: on a one-pager
    // "where am I" is the scroll spy's job (see bindSectionSpy() in the theme's
    // script.js), not a class on the link.
    $linkFor = fn (string $label) => (string) (preg_match('/<a\b[^>]*>\s*'.preg_quote($label, '/').'\s*<\/a>/', $html, $m) ? $m[0] : '');

    expect($linkFor('Projects'))->not->toContain('is-active');
});

it('shows only featured product categories in the storefront shop-by-category grid', function () {
    Setting::set('site_theme', 'ecommerce');

    $featured = Category::factory()->create([
        'type' => Category::TYPE_PRODUCT,
        'name' => ['en' => 'Mustard Oil', 'bn' => ''],
        'status' => 'active',
        'featured' => true,
    ]);
    $plain = Category::factory()->create([
        'type' => Category::TYPE_PRODUCT,
        'name' => ['en' => 'Plain Category', 'bn' => ''],
        'status' => 'active',
        'featured' => false,
    ]);

    Page::factory()->published()->create(['type' => Category::TYPE_PRODUCT, 'category_id' => $featured->id, 'title' => ['en' => 'Mustard Oil', 'bn' => ''], 'slug' => 'mustard-oil']);
    Page::factory()->published()->create(['type' => Category::TYPE_PRODUCT, 'category_id' => $plain->id, 'title' => ['en' => 'Plain Category', 'bn' => ''], 'slug' => 'plain-category']);

    $html = $this->get('/')->assertOk()->assertSee('Shop by category')->getContent();

    // Only the featured category is rendered as a grid card below the heading
    // — the header category dropdown intentionally still lists every active
    // category, so scope the assertion to the grid section itself.
    $grid = Str::after($html, 'Shop by category');
    expect($grid)->toContain('category/mustard-oil')
        ->and($grid)->not->toContain('category/plain-category');
});

it('applies the ecommerce theme primary & secondary colors to the storefront', function () {
    Setting::set('site_theme', 'ecommerce');
    Setting::set('theme_ecommerce_primary_color', '#c01616');
    Setting::set('theme_ecommerce_secondary_color', '#1e7bc4');

    $this->get('/')
        ->assertOk()
        ->assertSeeHtml('--color-brand: #c01616')
        ->assertSeeHtml('--color-secondary: #1e7bc4');
});

it('does not inject theme colors when the ecommerce theme is not active', function () {
    Setting::set('site_theme', 'portfolio');

    $this->get('/')
        ->assertOk()
        ->assertDontSee('--color-brand:');
});

it('shows a language switcher in the ecommerce header when more than one language is active', function () {
    Setting::set('site_theme', 'ecommerce');
    Language::create(['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'flag' => '🇧🇩', 'is_active' => true]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Change language', false)
        ->assertSee('বাংলা')
        ->assertSee('en', false);
});

it('switches the storefront language per-visitor via the lang query param', function () {
    Setting::set('site_theme', 'ecommerce');
    Language::create(['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    $this->get('/?lang=bn')
        ->assertOk()
        ->assertSessionHas('frontend_locale', 'bn');
});

it('hides the ecommerce language switcher while only one language is active', function () {
    Setting::set('site_theme', 'ecommerce');
    Language::create(['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true]);

    $this->get('/')->assertOk()->assertDontSee('Change language', false);
});

it('hides the ecommerce language switcher once disabled in settings', function () {
    Setting::set('site_theme', 'ecommerce');
    Setting::set('language_switcher_enabled', '0');
    Language::create(['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    $this->get('/')->assertOk()->assertDontSee('Change language', false);
});

it('ranks best-selling products by units sold on non-cancelled orders', function () {
    Setting::set('site_theme', 'ecommerce');

    $user = User::factory()->create();

    $top = Product::factory()->published()->create(['name' => ['en' => 'Top Seller', 'bn' => '']]);
    pairPageFor($top, 'product', 'top-seller', $user->id);
    $second = Product::factory()->published()->create(['name' => ['en' => 'Second Seller', 'bn' => '']]);
    pairPageFor($second, 'product', 'second-seller', $user->id);
    $cancelled = Product::factory()->published()->create(['name' => ['en' => 'Cancelled Product', 'bn' => '']]);
    pairPageFor($cancelled, 'product', 'cancelled-product', $user->id);

    $delivered = Order::factory()->status('delivered')->create();
    OrderItem::factory()->create(['order_id' => $delivered->id, 'product_id' => $top->id, 'quantity' => 5]);
    OrderItem::factory()->create(['order_id' => $delivered->id, 'product_id' => $second->id, 'quantity' => 2]);

    $cancelledOrder = Order::factory()->status('cancelled')->create();
    OrderItem::factory()->create(['order_id' => $cancelledOrder->id, 'product_id' => $cancelled->id, 'quantity' => 99]);

    $html = $this->get('/')->assertOk()->assertSee('Best sellers')->getContent();

    $grid = Str::between($html, 'Best sellers', 'New arrivals');
    expect($grid)->toContain('top-seller')
        ->and($grid)->toContain('second-seller')
        ->and($grid)->not->toContain('cancelled-product')
        ->and($grid)->toContain('5 sold')
        ->and($grid)->toContain('2 sold')
        ->and(strpos($grid, '/products/top-seller'))->toBeLessThan(strpos($grid, '/products/second-seller'));
});
