<?php

use App\Models\Language;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Frontend\LandingOrder;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Support\LandingTriggers;
use App\Support\Themes;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Blue Tea', 'bn' => ''], 'sort_order' => 0]);

    pairPageFor($product, 'product', 'blue-tea', User::factory()->create()->id);
});

it('renders the regular product page for a plain visit', function () {
    get('/products/blue-tea')
        ->assertOk()
        ->assertSee('Blue Tea')
        ->assertDontSee('id="order"', false);
});

it('renders the campaign landing page when the visit carries a fbclid', function () {
    get('/products/blue-tea?fbclid=egfad')
        ->assertOk()
        ->assertSee('Blue Tea')
        ->assertSee('id="order"', false);
});

it('renders the landing page chosen on the product, falling back to the default one', function () {
    $dir = base_path('themes/ecommerce/views/landing');
    file_put_contents($dir.'/zz-test-promo.blade.php', '<p>ZZ-TEST-PROMO</p>');

    try {
        Product::query()->update(['landing_page' => 'zz-test-promo']);
        get('/products/blue-tea?fbclid=egfad')->assertOk()->assertSee('ZZ-TEST-PROMO');

        get('/products/blue-tea')->assertDontSee('ZZ-TEST-PROMO');

        Product::query()->update(['landing_page' => 'removed-page']);
        get('/products/blue-tea?fbclid=egfad')->assertOk()->assertSee('id="order"', false);
    } finally {
        @unlink($dir.'/zz-test-promo.blade.php');
    }
});

it('ships default, basic and standard landing pages that all render', function () {
    expect(array_keys(Themes::landingPages('ecommerce')))->toBe(['basic', 'default', 'standard']);

    foreach (['basic', 'default', 'standard'] as $page) {
        Product::query()->update(['landing_page' => $page]);

        get('/products/blue-tea?fbclid=egfad')->assertOk()->assertSee('id="order"', false);
    }
});

it('places an order straight from the basic landing page without a cart', function () {
    $product = Product::first();
    $product->update(['landing_page' => 'basic', 'price' => 250, 'quantity' => 10, 'discount_price' => null]);

    $inside = ShippingMethod::create(['name' => 'Inside Dhaka', 'cost' => 70, 'status' => 'active']);

    get('/products/blue-tea?fbclid=egfad')->assertOk()->assertSee('Inside Dhaka');

    Livewire::test(LandingOrder::class, ['productId' => $product->id])
        ->assertSet('quantities', [0 => 1])
        ->call('increase', 0)
        ->set('customer_name', 'Rahim')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', 'House 1, Road 2, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = Order::firstOrFail();

    expect((float) $order->subtotal)->toBe(500.0)
        ->and((float) $order->shipping_cost)->toBe(70.0)
        ->and((float) $order->total)->toBe(570.0)
        ->and($order->customer_phone)->toBe('01712345678')
        ->and($order->items)->toHaveCount(1);
});

it('refuses a landing page order with no details', function () {
    $product = Product::first();

    Livewire::test(LandingOrder::class, ['productId' => $product->id])
        ->call('placeOrder')
        ->assertHasErrors(['customer_name', 'customer_phone', 'shipping_address']);

    expect(Order::count())->toBe(0);
});

it('places an order from the standard landing page form', function () {
    $product = Product::first();
    $product->update(['landing_page' => 'standard', 'price' => 250, 'quantity' => 10, 'discount_price' => null]);

    ShippingMethod::create(['name' => 'Outside Dhaka', 'cost' => 120, 'status' => 'active']);

    get('/products/blue-tea?fbclid=egfad')->assertOk()->assertSee('Outside Dhaka')->assertSee('id="order"', false);

    Livewire::test(LandingOrder::class, ['productId' => $product->id, 'layout' => 'wide'])
        ->assertSee('Your order')
        ->set('customer_name', 'Karim')
        ->set('customer_phone', '01812345678')
        ->set('shipping_address', 'Road 5, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect((float) Order::firstOrFail()->total)->toBe(370.0);
});

it('matches landing page rules against the query string', function () {
    $request = fn (string $uri) => Request::create($uri);

    expect(LandingTriggers::matches($request('/p?fbclid=abc')))->toBeTrue()
        ->and(LandingTriggers::matches($request('/p?fbclid=')))->toBeFalse()
        ->and(LandingTriggers::matches($request('/p')))->toBeFalse()
        ->and(LandingTriggers::matches($request('/p?utm_source=Facebook'), 'utm_source=facebook'))->toBeTrue()
        ->and(LandingTriggers::matches($request('/p?utm_source=google'), 'utm_source=facebook'))->toBeFalse()
        ->and(LandingTriggers::matches($request('/p?gclid=1'), "fbclid\ngclid"))->toBeTrue()
        ->and(LandingTriggers::matches($request('/p?fbclid=1'), ''))->toBeFalse();
});

it('follows the landing page rules saved in settings', function () {
    Setting::set(LandingTriggers::SETTING, 'gclid');

    get('/products/blue-tea?fbclid=egfad')->assertOk()->assertDontSee('id="order"', false);
    get('/products/blue-tea?gclid=xyz')->assertOk()->assertSee('id="order"', false);

    Setting::set(LandingTriggers::SETTING, '');

    get('/products/blue-tea?gclid=xyz')->assertOk()->assertDontSee('id="order"', false);
});

it('saves landing page rules from the admin products page and rejects bad ones', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    Livewire::actingAs($admin)->test(ProductsIndex::class)
        ->assertSet('landingTriggers', 'fbclid')
        ->set('landingTriggers', "gclid\nutm_source=facebook")
        ->call('saveLandingTriggers')
        ->assertHasNoErrors();

    expect(Setting::get(LandingTriggers::SETTING))->toBe("gclid\nutm_source=facebook");

    Livewire::actingAs($admin)->test(ProductsIndex::class)
        ->set('landingTriggers', 'not a rule')
        ->call('saveLandingTriggers')
        ->assertHasErrors('landingTriggers');
});

it('keeps the admin on the product form after saving', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $create = Livewire::actingAs($admin)->test(ProductForm::class)
        ->set('name', ['en' => 'Green Tea', 'bn' => ''])
        ->set('price', '120')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('sku', null)->latest('id')->first();
    expect($product)->not->toBeNull();

    $create->assertRedirect(route('admin.products.edit', $product->id));

    Livewire::actingAs($admin)->test(ProductForm::class, ['id' => $product->id])
        ->set('price', '130')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    expect((float) $product->fresh()->price)->toBe(130.0);
});
