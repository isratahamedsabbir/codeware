<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\InformationMenuSeeder;
use Database\Seeders\QuickLinksMenuSeeder;
use Database\Seeders\RolePermissionSeeder;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

it('renders the information menu links in the footer', function () {
    $this->seed(InformationMenuSeeder::class);

    get('/shop')
        ->assertOk()
        ->assertSee('Information')
        ->assertSee('About Us', false)
        ->assertSee('Contact Us', false)
        ->assertSee('FAQ', false)
        ->assertSee('/about')
        ->assertSee('/contact')
        ->assertSee('/faq');
});

it('renders the quick links menu in the footer', function () {
    $this->seed(QuickLinksMenuSeeder::class);

    get('/shop')
        ->assertOk()
        ->assertSee('Quick Links')
        ->assertSee('My Favorites', false)
        ->assertSee('/favorites')
        ->assertSee('/shop');
});

it('shows the contact phone and email in the footer', function () {
    Setting::set('contact_email', 'support@example.com');
    Setting::set('contact_phone', '+8801700000000');

    get('/shop')
        ->assertOk()
        ->assertSee('support@example.com')
        ->assertSee('+8801700000000')
        ->assertSee('mailto:support@example.com')
        ->assertSee('tel:+8801700000000');
});

it('shows the contact phone in the header bar, not the email', function () {
    Setting::set('contact_phone', '+8801700000000');

    get('/shop')
        ->assertOk()
        ->assertSee('+8801700000000')
        ->assertSee('tel:+8801700000000')
        ->assertDontSee('mailto:');
});
