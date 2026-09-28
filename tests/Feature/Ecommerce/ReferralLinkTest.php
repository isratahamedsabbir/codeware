<?php

use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Models\Language;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderPlacement;
use App\Support\Referral;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    $this->product = Product::factory()->published()->create([
        'name' => ['en' => 'Referral Tea', 'bn' => ''],
        'sort_order' => 0,
        'quantity' => 10,
        'price' => 100,
    ]);

    pairPageFor($this->product, 'product', 'referral-tea', User::factory()->create()->id);
});

/**
 * Places a one-line order through the real placement pipeline, so the ref is
 * read at the same point a checkout reads it rather than being written straight
 * onto the row.
 */
function placeOneOrder(): Order
{
    return app(OrderPlacement::class)->placeProducts(
        [['product_id' => test()->product->id, 'quantity' => 1]],
        [
            'customer_name' => 'Test Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '01700000000',
            'payment_method' => 'cod',
        ],
    );
}

it('captures the referrer behind a ?ref= link on a storefront page', function () {
    $referrer = User::factory()->create();

    $this->get('/products/referral-tea?ref='.$referrer->code)->assertOk();

    expect(Referral::pendingId())->toBe($referrer->id);
});

it('captures the referrer on any storefront page, not just the shared one', function () {
    // A link is opened, browsed and only then bought — the shop index in
    // between is not where the buyer ends up, so the ref cannot be tied to the
    // page the link happened to point at.
    $referrer = User::factory()->create();

    $this->get('/shop?ref='.$referrer->code)->assertOk();

    expect(Referral::pendingId())->toBe($referrer->id);
});

it('tags a signed-in customer\'s share links with their own ref code', function () {
    $sharer = User::factory()->create();

    $this->actingAs($sharer);

    $html = $this->get('/products/referral-tea')->assertOk()->getContent();

    // Both carriers of the link have to carry it: the network hrefs, and the
    // clipboard button that people use more than any of them.
    expect($html)->toContain('data-share-copy="'.url('/products/referral-tea').'?ref='.$sharer->code.'"')
        ->and($html)->toContain(rawurlencode(url('/products/referral-tea').'?ref='.$sharer->code));
});

it('leaves the ref off a guest\'s share links', function () {
    $html = $this->get('/products/referral-tea')->assertOk()->getContent();

    // A guest has no USR- code, so there is nobody to credit — and a ref that
    // resolved to nobody would still put the parameter on every share.
    expect($html)->toContain('data-share-copy="'.url('/products/referral-tea').'"')
        ->and($html)->not->toContain('?ref=');
});

it('keeps the ref out of the page\'s own canonical', function () {
    $sharer = User::factory()->create();

    $this->actingAs($sharer);

    $html = $this->get('/products/referral-tea')->assertOk()->getContent();

    // og:url is what a social network scrapes, and a ref-tagged canonical would
    // let one customer's code become the page's indexed identity for everyone.
    expect($html)->toContain('<meta property="og:url" content="'.url('/products/referral-tea').'">')
        ->and($html)->toContain('<link rel="canonical" href="'.url('/products/referral-tea').'">');
});

it('stamps the referrer onto an order placed through the link', function () {
    $referrer = User::factory()->create();
    $buyer = User::factory()->create();

    // Opened as a guest, which is the ordinary case: the person a link was sent
    // to has no session yet.
    $this->get('/products/referral-tea?ref='.$referrer->code)->assertOk();

    $this->actingAs($buyer);

    $order = placeOneOrder();

    expect($order->ref)->toBe($referrer->id)
        ->and($order->referrer)->not->toBeNull()
        ->and($order->referrer->is($referrer))->toBeTrue()
        // The buyer and the referrer are different accounts, which is the whole
        // point — a self-referral would make this assertion pass trivially.
        ->and($order->user_id)->toBe($buyer->id);
});

it('leaves a directly-arrived order with no referrer', function () {
    $this->actingAs(User::factory()->create());

    expect(placeOneOrder()->ref)->toBeNull();
});

it('refuses to let a customer refer themselves', function () {
    $sharer = User::factory()->create();

    $this->actingAs($sharer);
    $this->get('/products/referral-tea?ref='.$sharer->code)->assertOk();

    expect(Referral::pendingId())->toBeNull()
        ->and(placeOneOrder()->ref)->toBeNull();
});

it('refuses a self-referral claimed through a session that predates the login', function () {
    // The case the capture-time check cannot cover: the link is opened as a
    // guest, and the account behind it only signs in afterwards. The session
    // outlives the login, so the guard that matters is the one at order time.
    $sharer = User::factory()->create();

    $this->get('/products/referral-tea?ref='.$sharer->code)->assertOk();

    expect(Referral::pendingId())->toBe($sharer->id);

    $this->actingAs($sharer);

    expect(placeOneOrder()->ref)->toBeNull();
});

it('attributes the order to the last link opened', function () {
    $first = User::factory()->create();
    $last = User::factory()->create();

    $this->get('/products/referral-tea?ref='.$first->code)->assertOk();
    $this->get('/products/referral-tea?ref='.$last->code)->assertOk();

    $this->actingAs(User::factory()->create());

    expect(placeOneOrder()->ref)->toBe($last->id);
});

it('ignores an unrecognised ref rather than discarding a real one', function () {
    $referrer = User::factory()->create();

    $this->get('/products/referral-tea?ref='.$referrer->code)->assertOk();

    // A mistyped, truncated or hand-edited link must not be able to wipe an
    // attribution that is already sitting in the session.
    $this->get('/products/referral-tea?ref=USR-NOPE1234')->assertOk();
    $this->get('/products/referral-tea?ref=')->assertOk();
    $this->get('/products/referral-tea?ref=admin-panel')->assertOk();

    expect(Referral::pendingId())->toBe($referrer->id);
});

it('spends the link on the first order only', function () {
    $referrer = User::factory()->create();

    $this->get('/products/referral-tea?ref='.$referrer->code)->assertOk();

    $this->actingAs(User::factory()->create());

    expect(placeOneOrder()->ref)->toBe($referrer->id)
        ->and(placeOneOrder()->ref)->toBeNull();
});

it('keeps the referrer when a placement fails, so the retry is not lost', function () {
    $referrer = User::factory()->create();

    $this->get('/products/referral-tea?ref='.$referrer->code)->assertOk();

    $this->actingAs(User::factory()->create());

    // A combination that is no longer in the catalog fails the placement. The
    // attribution is spent by a *committed* order, so a checkout the shopper
    // has to redo must not have already consumed it.
    expect(fn () => app(OrderPlacement::class)->placeProducts(
        [['product_id' => $this->product->id, 'quantity' => 1, 'attributes' => ['size' => 'no-longer-sold']]],
        [
            'customer_name' => 'Test Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '01700000000',
            'payment_method' => 'cod',
        ],
    ))->toThrow(ValidationException::class)
        ->and(Referral::pendingId())->toBe($referrer->id);

    expect(placeOneOrder()->ref)->toBe($referrer->id);
});

it('clears the ref on the order when the referring account is deleted', function () {
    $referrer = User::factory()->create();
    $order = Order::factory()->create(['ref' => $referrer->id]);

    $referrer->delete();

    // The FK is nullOnDelete, so a removed account can never leave an order
    // pointing at a row that no longer exists.
    expect($order->fresh()->ref)->toBeNull()
        ->and($order->fresh()->referrer)->toBeNull();
});

it('shows who referred each order in the admin list', function () {
    $referrer = User::factory()->create(['name' => 'Referrer Person']);

    Order::factory()->create(['ref' => $referrer->id, 'customer_name' => 'Referred Order']);
    Order::factory()->create(['customer_name' => 'Direct Order']);

    $this->actingAs($this->admin);

    Livewire::test(OrdersIndex::class)
        ->assertSee('Referrer Person')
        ->assertSee($referrer->code)
        ->assertSee('Referred Order')
        // An order with no referrer still has to render — as an em-dash, not as
        // a missing cell that reads like unloaded data.
        ->assertSee('Direct Order')
        ->assertSee('—');
});
