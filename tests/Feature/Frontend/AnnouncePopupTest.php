<?php

use App\Livewire\Frontend\AnnouncePopup;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use Livewire\Livewire;

it('renders nothing when the popup is disabled in settings', function () {
    Setting::set('popup_enabled', false);
    Setting::set('popup_title', 'Welcome to our store');

    Livewire::test(AnnouncePopup::class)
        ->assertDontSee('codeware_popup_dismissed')
        ->assertDontSee('Welcome to our store');
});

it('renders the popup overlay with its configured content when enabled', function () {
    Setting::set('popup_enabled', true);
    Setting::set('popup_image', 'http://codeware.test/storage/media/banner.jpg');
    Setting::set('popup_title', 'Welcome to our store');
    Setting::set('popup_description', 'Get 10% off your first order.');
    Setting::set('popup_button_label', 'Shop Now');
    Setting::set('popup_button_url', '/shop');

    $html = Livewire::test(AnnouncePopup::class)->html();

    expect($html)
        ->toContain('codeware_popup_dismissed')
        ->toContain('Welcome to our store')
        ->toContain('Get 10% off your first order.')
        ->toContain('Shop Now')
        ->toContain('/shop')
        ->toContain('storage/media/banner.jpg');
});

it('requires the button link to render the button', function () {
    Setting::set('popup_enabled', true);
    Setting::set('popup_title', 'Welcome to our store');
    Setting::set('popup_button_label', 'Shop Now');
    Setting::set('popup_button_url', '');

    $html = Livewire::test(AnnouncePopup::class)->html();

    expect($html)
        ->toContain('Welcome to our store')
        ->not->toContain('Shop Now');
});

it('shows the popup on ecommerce pages when enabled and hides it when disabled', function () {
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
    MenuItem::create(['group' => 'frontend', 'label' => 'Home', 'url' => '/', 'sort_order' => 0, 'is_active' => true]);
    Setting::set('site_theme', 'ecommerce');
    Setting::set('popup_title', 'Welcome to our store');

    Setting::set('popup_enabled', true);
    $this->get('/')->assertOk()->assertSee('Welcome to our store');

    Setting::set('popup_enabled', false);
    $this->get('/')->assertOk()->assertDontSee('Welcome to our store');
});
