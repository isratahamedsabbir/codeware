<?php

use App\Livewire\Frontend\AddToCartButton;
use App\Livewire\Frontend\CartCount;
use App\Livewire\Frontend\CartPage;
use App\Livewire\Frontend\Checkout;
use App\Models\Language;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

function cartProduct(string $slug, array $attributes = []): Product
{
    $product = Product::factory()->published()->create($attributes + ['sort_order' => 0, 'quantity' => 10]);

    pairPageFor($product, 'product', $slug, User::factory()->create()->id);

    return $product;
}

function checkoutFields(): array
{
    return [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
    ];
}

it('adds a product to the cart from its card', function () {
    $product = cartProduct('cartable', ['name' => ['en' => 'Cartable Thing', 'bn' => ''], 'price' => 100]);

    Livewire::test(AddToCartButton::class, ['productId' => $product->id])
        ->assertSet('inStock', true)
        ->call('add')
        ->assertSet('added', true);

    expect(session('cart'))->toBe([$product->id => 1]);
});

it('does not let you add an out-of-stock or upcoming product to the cart', function () {
    $outOfStock = cartProduct('no-stock', ['name' => ['en' => 'No Stock', 'bn' => ''], 'quantity' => 0]);
    $upcoming = cartProduct('soon', ['name' => ['en' => 'Coming Soon', 'bn' => ''], 'is_upcoming' => true]);

    Livewire::test(AddToCartButton::class, ['productId' => $outOfStock->id])->call('add');
    Livewire::test(AddToCartButton::class, ['productId' => $upcoming->id])->call('add');

    expect(session('cart', []))->toBe([]);
});

it('renders an add-to-cart button on product cards and the header cart count', function () {
    cartProduct('card-item', ['name' => ['en' => 'Cart Card Item', 'bn' => '']]);

    get('/shop')
        ->assertOk()
        ->assertSee('Add to cart');

    Livewire::test(CartCount::class)->assertSet('count', 0);

    Livewire::test(AddToCartButton::class, ['productId' => Product::first()->id])->call('add');

    Livewire::test(CartCount::class)->assertSet('count', 1);
});

it('renders the cart page with its lines and updates quantities', function () {
    $product = cartProduct('in-cart', ['name' => ['en' => 'In Cart Item', 'bn' => ''], 'price' => 50]);
    Cart::add($product->id, 2);

    Livewire::test(CartPage::class)
        ->assertSet('count', 2)
        ->assertSet('subtotal', 100)
        ->assertSee('In Cart Item')
        ->call('increase', $product->id)
        ->assertSet('count', 3)
        ->assertSet('subtotal', 150)
        ->call('decrease', $product->id)
        ->assertSet('count', 2)
        ->assertSet('subtotal', 100);
});

it('removes a line from the cart page and can clear the whole cart', function () {
    $a = cartProduct('cart-a', ['name' => ['en' => 'Cart A', 'bn' => '']]);
    $b = cartProduct('cart-b', ['name' => ['en' => 'Cart B', 'bn' => '']]);
    Cart::add($a->id, 1);
    Cart::add($b->id, 1);

    Livewire::test(CartPage::class)
        ->call('remove', $a->id)
        ->assertSet('count', 1)
        ->assertDontSee('Cart A')
        ->call('clear')
        ->assertSet('count', 0)
        ->assertDontSee('Cart B');
});

it('renders the cart page route and the checkout page route', function () {
    cartProduct('server-cart-item', ['name' => ['en' => 'Server Cart Item', 'bn' => '']]);

    get('/cart')->assertOk()->assertSee('My cart');
    get('/checkout')->assertOk()->assertSee('Checkout');
});

it('places an order from the cart and clears the cart', function () {
    $product = cartProduct('checkout-me', ['name' => ['en' => 'Checkout Me', 'bn' => ''], 'price' => 400]);
    Cart::add($product->id, 2);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->set('payment_method', 'cod')
        ->call('placeOrder')
        ->assertRedirect(route('checkout.confirmation', Order::sole()->order_number));

    $order = Order::sole();

    expect($order->customer_email)->toBe('jane@example.com')
        ->and($order->payment_method)->toBe('cod')
        ->and($order->total)->toEqual('800.00')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(2);

    expect(session('cart', []))->toBe([]);
});

it('shows the confirmation page only for the order placed in this session', function () {
    $product = cartProduct('confirmed', ['name' => ['en' => 'Confirmed Item', 'bn' => '']]);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->call('placeOrder');

    $order = Order::sole();

    get(route('checkout.confirmation', $order->order_number))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Order placed');

    get('/order-confirmation/ORD-AAAAAAAA')->assertRedirect(route('shop'));
});

it('offers a working invoice download on the order confirmation page', function () {
    $product = Product::factory()->published()->create(['name' => ['en' => 'Invoice Item', 'bn' => ''], 'quantity' => 5, 'price' => 300]);
    pairPageFor($product, 'product', 'invoice-item', User::factory()->create()->id);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->call('placeOrder');

    $order = Order::sole();
    $downloadUrl = URL::signedRoute('invoices.public.download', ['order' => $order->order_number]);

    get(route('checkout.confirmation', $order->order_number))
        ->assertOk()
        ->assertSee('Download invoice (PDF)')
        ->assertSee($downloadUrl, false)
        ->assertSee(URL::signedRoute('invoices.public.show', ['order' => $order->order_number]), false);

    get($downloadUrl)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('blocks checkout while the shop is closed', function () {
    Setting::set('shop_enabled', '0');

    $product = cartProduct('closed-shop', ['name' => ['en' => 'Closed Shop', 'bn' => '']]);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->call('placeOrder')
        ->assertStatus(503);

    expect(Order::count())->toBe(0);
});

it('validates the checkout form fields', function () {
    $product = cartProduct('validation', ['name' => ['en' => 'Validation Item', 'bn' => '']]);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->set('customer_name', '')
        ->set('customer_email', 'not-an-email')
        ->set('customer_phone', '')
        ->set('shipping_address', '')
        ->call('placeOrder')
        ->assertHasErrors([
            'customer_name',
            'customer_email',
            'customer_phone',
            'shipping_address',
        ]);

    expect(Order::count())->toBe(0);
    expect(session('cart'))->toBe([$product->id => 1]);
});

function cartVariantProduct(string $slug): Product
{
    $product = Product::factory()->published()->create([
        'sort_order' => 0,
        'quantity' => 10,
        'price' => 20,
        'variations' => [
            ['attributes' => ['Color' => 'Red', 'Size' => 'M'], 'price' => 22, 'discount_price' => 18, 'quantity' => 5, 'visible' => true],
            ['attributes' => ['Color' => 'Blue', 'Size' => 'M'], 'price' => 22, 'discount_price' => null, 'quantity' => 0, 'visible' => true],
        ],
    ]);

    pairPageFor($product, 'product', $slug, User::factory()->create()->id);

    return $product;
}

it('shows an Add to cart picker button on variant product cards and a plain add button otherwise', function () {
    $variant = cartVariantProduct('variant-card');
    get('/shop')
        ->assertOk()
        ->assertSee('Add to cart')
        ->assertSee('/products/variant-card');

    // The card's option popup is server-rendered (hidden until opened), so
    // its swatches are in the page HTML — the attribute groups plus values.
    $html = get('/shop')->assertOk()->getContent();

    expect(str_contains($html, 'Color'))->toBeTrue()
        ->and(str_contains($html, 'Red'))->toBeTrue()
        ->and(str_contains($html, 'Blue'))->toBeTrue();

    $base = cartProduct('plain-card', ['name' => ['en' => 'Plain Card', 'bn' => '']]);
    get('/shop')
        ->assertOk()
        ->assertSee('Add to cart')
        ->assertSee('/products/plain-card');
});

it('adds a variant combination through the component and keeps combos as separate lines', function () {
    $product = cartVariantProduct('variant-add');
    $product->update(['variations' => [
        ['attributes' => ['Color' => 'Red', 'Size' => 'M'], 'price' => 22, 'discount_price' => 18, 'quantity' => 5, 'visible' => true],
        ['attributes' => ['Color' => 'Blue', 'Size' => 'M'], 'price' => 22, 'discount_price' => null, 'quantity' => 5, 'visible' => true],
    ]]);

    Livewire::test(AddToCartButton::class, ['productId' => $product->id])
        ->call('add', ['Color' => 'Red', 'Size' => 'M'])
        ->assertSet('added', true);

    Livewire::test(AddToCartButton::class, ['productId' => $product->id])
        ->call('add', ['Color' => 'Blue', 'Size' => 'M'])
        ->assertSet('added', true);

    expect(session('cart'))->toHaveCount(2);
    expect(array_sum(session('cart')))->toBe(2);
});

it('rejects a vanished or out-of-stock combination through the component', function () {
    $product = cartVariantProduct('variant-validated');

    Livewire::test(AddToCartButton::class, ['productId' => $product->id])
        ->call('add', ['Color' => 'Red', 'Size' => 'XL'])
        ->assertSet('added', false)
        ->assertHasErrors('options');

    Livewire::test(AddToCartButton::class, ['productId' => $product->id])
        ->call('add', ['Color' => 'Blue', 'Size' => 'M'])
        ->assertSet('added', false)
        ->assertHasErrors('options');

    expect(session('cart', []))->toBe([]);
});

it('blocks a card add when the base product line would bypass the picker', function () {
    $product = cartVariantProduct('variant-requires-options');

    Livewire::test(AddToCartButton::class, ['productId' => $product->id, 'requiresOptions' => true, 'hasVariations' => true])
        ->call('add')
        ->assertSet('added', false);

    expect(session('cart', []))->toBe([]);
});

it('places an order with a variant line snapshotted with options and combo price', function () {
    $product = cartVariantProduct('variant-order');

    Cart::add($product->id, 2, ['Color' => 'Red', 'Size' => 'M']);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->set('payment_method', 'cod')
        ->call('placeOrder')
        ->assertRedirect(route('checkout.confirmation', Order::sole()->order_number));

    $order = Order::sole();

    expect($order->items)->toHaveCount(1);
    $item = $order->items->first();

    expect($item->type)->toBe('product')
        ->and($item->product_id)->toBe($product->id)
        ->and(collect($item->variations)->sortKeys()->all())->toBe(['Color' => 'Red', 'Size' => 'M'])
        ->and((float) $item->unit_price)->toBe(22.0)
        ->and((float) $item->line_total)->toBe(36.0)
        ->and($order->subtotal)->toEqual('36.00')
        ->and($order->total)->toEqual('36.00');

    expect(session('cart', []))->toBe([]);
});
