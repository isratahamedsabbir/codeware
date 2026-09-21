<?php

use App\Livewire\Frontend\Checkout;
use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Cart;
use Livewire\Livewire;

it('renders checkout form fields with visible borders and payment methods', function () {
    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Render Me', 'bn' => ''], 'sort_order' => 0, 'quantity' => 10, 'price' => 100]);
    pairPageFor($product, 'product', 'render-me', User::factory()->create()->id);

    Cart::add($product->id, 1);

    $html = Livewire::test(Checkout::class)->html();

    expect($html)
        ->toContain('border-zinc-300')
        ->toContain('id="customer_name"')
        ->toContain('id="customer_email"')
        ->toContain('id="customer_phone"')
        ->toContain('id="shipping_address"')
        ->toContain('Cash on Delivery')
        ->toContain('Place order');
});
