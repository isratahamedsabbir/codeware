<?php

use App\Livewire\Admin\FlashDeals\Form as FlashDealForm;
use App\Models\FlashDeal;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Cart;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function flashProduct(float $price = 1000, ?float $discountPrice = null): Product
{
    $product = Product::factory()->published()->create(['price' => $price, 'discount_price' => $discountPrice, 'quantity' => 10]);
    Page::create([
        'type' => 'product',
        'product_id' => $product->id,
        'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'Flash product '.$product->id],
        'status' => 'active',
    ]);

    return $product->fresh();
}

function dealFor(Product $product, array $attributes = []): FlashDeal
{
    $deal = FlashDeal::factory()->create(array_merge(['type' => 'percentage', 'value' => 20], $attributes));
    $deal->products()->attach($product->id);

    return $deal;
}

it('only treats a deal as live between its start and end while switched on', function () {
    FlashDeal::factory()->create(['name' => 'live']);
    FlashDeal::factory()->inactive()->create(['name' => 'off']);
    FlashDeal::factory()->expired()->create(['name' => 'ended']);
    FlashDeal::factory()->upcoming()->create(['name' => 'later']);

    expect(FlashDeal::live()->pluck('name')->all())->toBe(['live']);
});

it('lowers the price of a product in a live deal without touching the product', function () {
    $product = flashProduct(1000);
    dealFor($product);

    expect($product->effectiveDiscount())->toBe(800.0)
        ->and($product->fresh()->discount_price)->toBeNull();
});

it('uses the cheaper of the product sale price and the flash deal', function () {
    $product = flashProduct(1000, 900);
    dealFor($product, ['value' => 20]); // 800

    expect($product->effectiveDiscount())->toBe(800.0);

    $cheaper = flashProduct(1000, 700);
    dealFor($cheaper, ['value' => 20]);

    expect($cheaper->effectiveDiscount())->toBe(700.0);
});

it('stops discounting once the deal ends or is switched off', function () {
    $product = flashProduct(1000);
    $deal = dealFor($product);
    expect($product->fresh()->effectiveDiscount())->toBe(800.0);

    $deal->update(['status' => 'inactive']);
    expect($product->fresh()->effectiveDiscount())->toBeNull();

    $deal->update(['status' => 'active', 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);
    expect($product->fresh()->effectiveDiscount())->toBeNull();
});

it('charges the flash price in the cart', function () {
    $product = flashProduct(1000);
    dealFor($product);

    Cart::add($product->id, 2);

    $line = Cart::lines()->first();

    expect($line['discount_price'])->toBe(800.0)
        ->and($line['line_total'])->toBe(1600.0);
});

it('lists only live deals, with their prices, on the public API', function () {
    $product = flashProduct(1000);
    dealFor($product, ['name' => 'Eid Sale']);
    FlashDeal::factory()->expired()->create(['name' => 'Old Sale'])->products()->attach(flashProduct()->id);

    $this->getJson('/api/v1/flash-deals')
        ->assertOk()
        ->assertJsonPath('meta.has_live_deals', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Eid Sale')
        ->assertJsonPath('data.0.products.0.price', 1000)
        ->assertJsonPath('data.0.products.0.deal_price', 800)
        ->assertJsonPath('data.0.products.0.discount_percent', 20);
});

it('reports no live deals from the API when there are none', function () {
    $this->getJson('/api/v1/flash-deals')
        ->assertOk()
        ->assertJsonPath('meta.has_live_deals', false)
        ->assertJsonCount(0, 'data');
});

it('404s the API for a deal that is not live', function () {
    $deal = FlashDeal::factory()->expired()->create();
    $deal->products()->attach(flashProduct()->id);

    $this->getJson("/api/v1/flash-deals/{$deal->id}")->assertNotFound();
});

it('shows the Flash Deals button after the menu only while a deal is live', function () {
    Setting::set('site_theme', 'ecommerce');

    $this->get('/')->assertOk()->assertDontSee('Flash Deals');

    dealFor(flashProduct());

    $this->get('/')->assertOk()->assertSee(route('flash-deals'), false)->assertSee('Flash Deals');
});

it('renders the flash deals page with the deal price', function () {
    Setting::set('site_theme', 'ecommerce');
    dealFor(flashProduct(1000), ['name' => 'Eid Sale']);

    $this->get(route('flash-deals'))
        ->assertOk()
        ->assertSee('Eid Sale')
        ->assertSee(format_money(800));
});

it('shows an empty state on the flash deals page when nothing is live', function () {
    Setting::set('site_theme', 'ecommerce');

    $this->get(route('flash-deals'))->assertOk()->assertSee('No flash deals right now');
});

describe('admin', function () {
    beforeEach(function () {
        $this->seed(RolePermissionSeeder::class);
        $this->actingAs(User::factory()->admin()->create());
    });

    it('creates an inactive flash deal with its products', function () {
        $product = flashProduct();

        Livewire::test(FlashDealForm::class)
            ->set('name', 'Eid Sale')
            ->set('type', 'percentage')
            ->set('value', '25')
            ->set('starts_at', now()->format('Y-m-d\TH:i'))
            ->set('ends_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('product_ids', [$product->id])
            ->call('save')
            ->assertHasNoErrors();

        $deal = FlashDeal::firstOrFail();

        expect($deal->status)->toBe('inactive')
            ->and($deal->products->pluck('id')->all())->toBe([$product->id]);
    });

    it('requires an end after the start and at least one product', function () {
        Livewire::test(FlashDealForm::class)
            ->set('name', 'Bad')
            ->set('value', '10')
            ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('ends_at', now()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasErrors(['ends_at', 'product_ids']);
    });

    it('lists flash deals in the admin', function () {
        FlashDeal::factory()->create(['name' => 'Eid Sale']);

        $this->get(route('admin.flash-deals'))->assertOk()->assertSee('Eid Sale');
        $this->get(route('admin.flash-deals.create'))->assertOk();
    });
});
