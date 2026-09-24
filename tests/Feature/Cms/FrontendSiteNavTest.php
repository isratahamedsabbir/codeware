<?php

use App\Models\Category;
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

    // The default theme's homepage is a bare login/dashboard card with no nav
    // — the pages-list-driven nav lives on its other pages (about/contact/faq).
    $this->get('/about')->assertOk()->assertSee('About Us')->assertDontSee('RENAMED');
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
