<?php

use App\Models\Language;
use App\Models\Order;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

it('renders the storefront login page on an ecommerce theme', function () {
    get('/login')
        ->assertOk()
        ->assertSee('data-login-form', false)
        ->assertSee(route('register'));
});

it('keeps the shared login page on non-ecommerce themes', function () {
    Setting::set('site_theme', 'default');

    get('/login')
        ->assertOk()
        ->assertDontSee('data-login-form', false)
        ->assertSee('Admin Panel');
});

it('sends guests at the account pages to the storefront login', function () {
    get('/account')->assertRedirect('/login');
    get('/account/orders')->assertRedirect('/login');
    get('/account/profile')->assertRedirect('/login');
});

it('redirects an ecommerce customer login to their account page', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com', 'password' => 'secret123']);

    post('/login', ['email' => 'customer@example.com', 'password' => 'secret123'])
        ->assertRedirect(route('account.dashboard'));
});

it('redirects a registration to the new customer account page', function () {
    post('/register', [
        'name' => 'New Customer',
        'email' => 'fresh@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])
        ->assertRedirect(route('account.dashboard'));

    expect(User::where('email', 'fresh@example.com')->first())->not->toBeNull();
    expect(User::where('email', 'fresh@example.com')->first()->hasRole('customer'))->toBeTrue();
});

it('keeps login redirecting to the admin dashboard on non-ecommerce themes', function () {
    Setting::set('site_theme', 'default');

    $customer = User::factory()->create(['email' => 'customer@example.com', 'password' => 'secret123']);

    post('/login', ['email' => 'customer@example.com', 'password' => 'secret123'])
        ->assertRedirect(config('fortify.home'));
});

it('shows the account dashboard previewing the customers recent orders', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);

    $mine = Order::factory()->create([
        'user_id' => $customer->id,
        'customer_email' => 'customer@example.com',
        'status' => 'processing',
    ]);

    $legacy = Order::factory()->create([
        'user_id' => null,
        'customer_email' => 'customer@example.com',
        'status' => 'delivered',
    ]);

    $someoneElses = Order::factory()->create(['status' => 'pending']);

    actingAs($customer)->get('/account')
        ->assertOk()
        ->assertSee($mine->order_number)
        ->assertSee($legacy->order_number)
        ->assertDontSee($someoneElses->order_number);
});

it('lists every order the customer can see', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);

    $orders = Order::factory()->count(3)->create([
        'user_id' => $customer->id,
        'customer_email' => 'customer@example.com',
    ]);

    actingAs($customer)->get('/account/orders')
        ->assertOk()
        ->assertSee($orders[0]->order_number)
        ->assertSee($orders[1]->order_number)
        ->assertSee($orders[2]->order_number);
});

it('renders the order detail page in the checkout card style with invoice links', function () {
    $customer = User::factory()->create(['email' => 'styled@example.com']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'customer_email' => 'styled@example.com',
        'shipping_address' => '12 Road, Dhaka',
        'shipping_method' => 'Express',
    ]);
    $order->items()->create(['type' => 'product', 'item_name' => 'Spearmint Tea', 'unit_price' => 450, 'quantity' => 2, 'line_total' => 900]);

    actingAs($customer)->get("/account/orders/{$order->order_number}")
        ->assertOk()
        ->assertSeeInOrder([$order->order_number, 'Download invoice (PDF)', 'Order items', 'Spearmint Tea', 'Total', 'Delivery details', '12 Road, Dhaka', 'Payment'], false)
        ->assertSee(\Illuminate\Support\Facades\URL::signedRoute('invoices.public.download', ['order' => $order->order_number]), false);
});

it('shows an order detail page only to the customer it belongs to', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);
    $other = User::factory()->create(['email' => 'other@example.com']);

    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'customer_email' => 'customer@example.com',
    ]);

    actingAs($customer)->get("/account/orders/{$order->order_number}")
        ->assertOk()
        ->assertSee($order->order_number);

    actingAs($other)->get("/account/orders/{$order->order_number}")->assertNotFound();

    auth()->logout();
    get("/account/orders/{$order->order_number}")->assertRedirect('/login');
});

it('shows legacy guest orders when the customers email matches', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);

    $legacy = Order::factory()->create([
        'user_id' => null,
        'customer_email' => 'CUSTOMER@example.com',
    ]);

    actingAs($customer)->get("/account/orders/{$legacy->order_number}")->assertOk();
});

it('renders the profile page for editing', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);

    actingAs($customer)->get('/account/profile')
        ->assertOk()
        ->assertSee($customer->name);
});

it('keeps the account area hidden on non-ecommerce themes', function () {
    Setting::set('site_theme', 'default');

    $customer = User::factory()->create(['email' => 'customer@example.com']);

    actingAs($customer)->get('/account')->assertNotFound();
    actingAs($customer)->get('/account/orders')->assertNotFound();
    actingAs($customer)->get('/account/profile')->assertNotFound();
});
