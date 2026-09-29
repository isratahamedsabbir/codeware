<?php

use App\Livewire\Frontend\Checkout;
use App\Mail\PasswordResetOtpMail;
use App\Mail\TemplateDrivenMail;
use App\Models\Coupon;
use App\Models\EmailTemplate;
use App\Models\Language;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/**
 * Guest checkout.
 *
 * The /checkout route is open to anyone, and App\Services\OrderPlacement gives
 * every order an owner by looking the submitted email up and creating the
 * account when it isn't there yet. These cover the two halves of that — the
 * account a brand-new email gets, and the existing account a returning one is
 * attached to instead.
 */
beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    // Deliberately not acting as anyone: every test here is a visitor with a
    // session cart and no account.
    auth()->logout();
});

function guestCartProduct(string $slug, array $attributes = []): Product
{
    $product = Product::factory()->published()->create($attributes + ['sort_order' => 0, 'quantity' => 10, 'price' => 100]);

    pairPageFor($product, 'product', $slug, User::factory()->create()->id);

    return $product;
}

function guestPlacesOrder(array $overrides = []): Order
{
    $product = guestCartProduct('guest-item', ['name' => ['en' => 'Guest Item', 'bn' => '']]);

    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->set('customer_name', $overrides['customer_name'] ?? 'Jane Doe')
        ->set('customer_email', $overrides['customer_email'] ?? 'jane@example.com')
        ->set('customer_phone', $overrides['customer_phone'] ?? '01712345678')
        ->set('shipping_address', $overrides['shipping_address'] ?? '123 Main St, Dhaka')
        ->set('payment_method', 'cod')
        ->call('placeOrder');

    return Order::sole();
}

it('creates an account from the email a guest orders with and attaches the order to it', function () {
    $order = guestPlacesOrder(['customer_name' => 'Jane Doe', 'customer_email' => 'jane@example.com']);

    $account = User::where('email', 'jane@example.com')->sole();

    expect($account->name)->toBe('Jane Doe')
        ->and($order->user_id)->toBe($account->id)
        ->and($order->customer_email)->toBe('jane@example.com');

    // A guest's order is an ordinary customer account: no roles means no admin,
    // staff, vendor or delivery power (the delivery rider is a role too — see
    // the replace_is_delivery_boy_with_role migration), and it starts unblocked.
    expect($account->roles)->toHaveCount(0)
        ->and($account->is_blocked)->toBeFalse()
        ->and(User::deliveryBoys()->pluck('id')->all())->toBe([]);
});

it('gives the account it creates a password nobody can sign in with, and lets a real one be claimed', function () {
    Mail::fake();

    guestPlacesOrder(['customer_email' => 'jane@example.com']);

    $account = User::where('email', 'jane@example.com')->sole();

    // The random password is never disclosed, so an empty or guessed one must
    // not open the account — Fortify's authenticateUsing runs Hash::check
    // directly against whatever is stored here.
    expect(Hash::check('', $account->password))->toBeFalse()
        ->and(Hash::check('password', $account->password))->toBeFalse();

    // The way back in is the ordinary password reset, which only needs the
    // email the order was placed with. The order itself already sent a claim
    // code, and asking for another straight away leaves that one in place.
    $code = null;
    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    post(route('password.email'), ['email' => 'jane@example.com'])->assertSessionHasNoErrors();

    post(route('password.verify.check'), ['code' => $code])->assertSessionHasNoErrors();
    post(route('password.update'), [
        'password' => 'a-password-of-my-own',
        'password_confirmation' => 'a-password-of-my-own',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('a-password-of-my-own', $account->fresh()->password))->toBeTrue();
});

it('attaches a guest order to the account that already has the email instead of creating a second one', function () {
    $existing = User::factory()->create([
        'name' => 'The Real Jane',
        'email' => 'jane@example.com',
        'password' => 'her-own-password',
    ]);

    $order = guestPlacesOrder(['customer_name' => 'J. Doe', 'customer_email' => 'jane@example.com']);

    expect(User::where('email', 'jane@example.com')->count())->toBe(1)
        ->and($order->user_id)->toBe($existing->id);

    // The order carries whatever the guest typed, but the account they already
    // had keeps its own name and password.
    expect($order->customer_name)->toBe('J. Doe')
        ->and($existing->fresh()->name)->toBe('The Real Jane')
        ->and(Hash::check('her-own-password', $existing->fresh()->password))->toBeTrue();
});

it('finds the existing account whatever case the guest typed the email in', function () {
    $existing = User::factory()->create(['name' => 'The Real Jane', 'email' => 'jane@example.com']);

    // User::email() lowercases every write, so a differently-cased lookup is
    // the only way to match it — otherwise this would try to create a duplicate
    // and the unique index would reject it.
    $order = guestPlacesOrder(['customer_email' => 'Jane@Example.COM']);

    expect(User::where('email', 'jane@example.com')->count())->toBe(1)
        ->and($order->user_id)->toBe($existing->id);
});

it('keeps one account across several guest orders from the same email', function () {
    $product = guestCartProduct('repeat-buyer', ['name' => ['en' => 'Repeat Item', 'bn' => '']]);

    foreach (range(1, 2) as $_) {
        Cart::add($product->id, 1);

        Livewire::test(Checkout::class)
            ->set('customer_name', 'Jane Doe')
            ->set('customer_email', 'jane@example.com')
            ->set('customer_phone', '01712345678')
            ->set('shipping_address', '123 Main St, Dhaka')
            ->set('payment_method', 'cod')
            ->call('placeOrder');
    }

    $account = User::where('email', 'jane@example.com')->sole();

    expect(Order::count())->toBe(2)
        ->and(Order::pluck('user_id')->unique()->all())->toBe([$account->id]);
});

it('shows a guest order in the account history of the account it created', function () {
    $order = guestPlacesOrder(['customer_email' => 'jane@example.com']);

    $account = User::where('email', 'jane@example.com')->sole();

    // Nothing had to sign in at checkout for this to line up — the order is
    // already attached, so the history is waiting whenever the customer gets
    // back in.
    actingAs($account)
        ->get('/account/orders')
        ->assertOk()
        ->assertSee($order->order_number);

    actingAs($account)
        ->get("/account/orders/{$order->order_number}")
        ->assertOk()
        ->assertSee($order->order_number);
});

it('does not create an account when the guest order never passes validation', function () {
    $product = guestCartProduct('invalid-guest', ['name' => ['en' => 'Invalid Guest', 'bn' => '']]);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->call('placeOrder')
        ->assertHasErrors('customer_phone');

    expect(Order::count())->toBe(0)
        ->and(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('leaves a signed-in shopper their own account even when they type another email', function () {
    $product = guestCartProduct('signed-in', ['name' => ['en' => 'Signed In Item', 'bn' => '']]);
    Cart::add($product->id, 1);

    $shopper = User::factory()->create(['name' => 'Signed In', 'email' => 'shopper@example.com']);
    actingAs($shopper);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Someone Else')
        ->set('customer_email', 'someone-else@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->set('payment_method', 'cod')
        ->call('placeOrder');

    // The session, not the form, decides who an order belongs to — a signed-in
    // shopper can't accidentally hand their order to another account.
    expect(Order::sole()->user_id)->toBe($shopper->id)
        ->and(User::where('email', 'someone-else@example.com')->exists())->toBeFalse();
});

it('still lets a guest fill in a coupon and have it applied to their order', function () {
    $product = guestCartProduct('coupon-item', ['name' => ['en' => 'Coupon Item', 'bn' => ''], 'price' => 500]);
    $coupon = Coupon::factory()->create([
        'code' => 'GUEST10',
        'type' => 'percentage',
        'value' => 10,
        'min_order_amount' => 100,
    ]);

    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->set('payment_method', 'cod')
        ->set('coupon_code', 'GUEST10')
        ->call('placeOrder');

    $order = Order::sole();

    expect($order->coupon_code)->toBe('GUEST10')
        ->and((float) $order->discount)->toBe(50.0)
        ->and($order->user_id)->toBe(User::where('email', 'jane@example.com')->sole()->id)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

it('serves the checkout page to a guest on the storefront', function () {
    $product = guestCartProduct('guest-page', ['name' => ['en' => 'Guest Page', 'bn' => '']]);
    Cart::add($product->id, 1);

    get('/checkout')
        ->assertOk()
        ->assertSee('Checkout')
        ->assertSee('Place order')
        ->assertSee('Checking out as a guest');
});

/**
 * The account a guest's order creates has a password nobody was ever told, so
 * without being handed a way to set one the order history would be unreachable:
 * the row exists, attached and waiting, with no door into it. These cover the
 * mail that opens that door.
 */
describe('account claim email', function () {
    it('emails a claim code when the guest order creates the account', function () {
        Mail::fake();

        $order = guestPlacesOrder(['customer_name' => 'Jane Doe', 'customer_email' => 'jane@example.com']);

        $account = User::where('email', 'jane@example.com')->sole();

        // The code is the only route into the account the order just attached
        // itself to, so it has to actually be sent — not merely reachable had
        // the customer thought to ask for it.
        $code = null;

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use ($account, &$code) {
            expect($mail->hasTo($account->email))->toBeTrue()
                ->and($mail->code)->toMatch('/^\d{6}$/')
                // The order email and the claim email are different things, and
                // the text should not blur them.
                ->and($mail->reason)->toBe('order');

            $code = $mail->code;

            return true;
        });

        // A claim code that arrives but cannot be redeemed would leave the
        // customer exactly as stuck as before, so it has to be a real one: the
        // customer asks the storefront for it, redeems it, and the order is on
        // the account that claim opens.
        $this->post(route('password.email'), ['email' => $account->email])->assertSessionHasNoErrors();
        $this->post(route('password.verify.check'), ['code' => $code])->assertSessionHasNoErrors();
        $this->post(route('password.update'), [
            'password' => 'a-password-of-my-own',
            'password_confirmation' => 'a-password-of-my-own',
        ])->assertSessionHasNoErrors();

        expect(Hash::check('a-password-of-my-own', $account->fresh()->password))->toBeTrue()
            ->and($order->user_id)->toBe($account->id)
            // The code proved control of the inbox, which is what verification
            // means.
            ->and($account->fresh()->email_verified_at)->not->toBeNull();
    });

    it('sends no claim email when the account already existed, since its owner knows their password', function () {
        Mail::fake();

        User::factory()->create(['name' => 'The Real Jane', 'email' => 'jane@example.com', 'password' => 'her-own-password']);

        $order = guestPlacesOrder(['customer_email' => 'jane@example.com']);

        // Mailing a reset code to a returning customer on every order would be
        // noise at best, and at worst a way to get into an inbox somebody else
        // is reading.
        Mail::assertNotSent(PasswordResetOtpMail::class);

        expect($order->user_id)->toBe(User::where('email', 'jane@example.com')->sole()->id);
    });

    it('sends no claim email to a signed-in shopper', function () {
        Mail::fake();

        $shopper = User::factory()->create(['name' => 'Signed In', 'email' => 'shopper@example.com']);
        actingAs($shopper);

        $product = guestCartProduct('signed-in-claim', ['name' => ['en' => 'Signed In Item', 'bn' => '']]);
        Cart::add($product->id, 1);

        Livewire::test(Checkout::class)
            ->set('customer_name', 'Signed In')
            ->set('customer_email', 'shopper@example.com')
            ->set('customer_phone', '01712345678')
            ->set('shipping_address', '123 Main St, Dhaka')
            ->set('payment_method', 'cod')
            ->call('placeOrder');

        Mail::assertNotSent(PasswordResetOtpMail::class);
    });

    it('keeps the order even when the claim email cannot be sent', function () {
        // The order is already committed by the time this mail goes out, and a
        // customer who never received the code can still ask for one themselves
        // from the storefront's forgot-password page — so a mail failure is
        // logged, not thrown, and the order stands. Only the claim mail fails
        // here; the order's own mail is not what's under test.
        Mail::shouldReceive('to')->andReturnUsing(fn ($recipients) => new class($recipients)
        {
            public function __construct(private array|string $recipients) {}

            public function send($mailable = null): void
            {
                if ($mailable instanceof PasswordResetOtpMail) {
                    throw new RuntimeException('SMTP connection refused');
                }
            }
        });

        $order = guestPlacesOrder(['customer_email' => 'jane@example.com']);

        expect($order->exists)->toBeTrue()
            ->and(Order::count())->toBe(1)
            ->and(User::where('email', 'jane@example.com')->exists())->toBeTrue();
    });
});

describe('order emails only describe committed orders', function () {
    it('sends nothing when the order is rolled back after being created', function () {
        Mail::fake();
        Notification::fake();
        EmailTemplate::factory()->create(['key' => 'order_confirmation', 'active' => true]);
        EmailTemplate::factory()->create(['key' => 'order_admin_notification', 'active' => true]);
        Setting::set('order_email', 'shop-admin@example.com');

        // The `created` event fires from inside the placement transaction, so
        // the sends are deferred to the commit. Anything that fails after the
        // order row is written must leave the customer's inbox untouched —
        // otherwise they get a confirmation, and an admin notification, for an
        // order that does not exist.
        try {
            DB::transaction(function () {
                Order::factory()->create(['customer_email' => 'jane@example.com']);

                throw new RuntimeException('something after the order failed');
            });
        } catch (RuntimeException) {
            // the deliberate rollback
        }

        expect(Order::count())->toBe(0);

        Mail::assertNothingSent();
        Notification::assertNothingSent();
    });

    it('does send once that transaction commits', function () {
        Mail::fake();
        EmailTemplate::factory()->create(['key' => 'order_confirmation', 'active' => true]);
        EmailTemplate::factory()->create(['key' => 'order_admin_notification', 'active' => true]);
        Setting::set('order_email', 'shop-admin@example.com');

        DB::transaction(fn () => Order::factory()->create(['customer_email' => 'jane@example.com']));

        Mail::assertSent(TemplateDrivenMail::class, fn ($mail) => $mail->hasTo('jane@example.com'));
        Mail::assertSent(TemplateDrivenMail::class, fn ($mail) => $mail->hasTo('shop-admin@example.com'));
    });
});
