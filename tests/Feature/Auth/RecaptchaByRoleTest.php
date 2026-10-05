<?php

/*
|--------------------------------------------------------------------------
| reCAPTCHA, per role
|--------------------------------------------------------------------------
|
| The switch is on the role, so "is the captcha on?" stopped being a property of
| the page and became a property of the account signing in. That splits the old
| single question in two, and the two are easy to confuse:
|
|   - enabled():      should the widget be rendered at all? The account is not
|                     known yet at page load, so this can only ask whether *any*
|                     role wants one. Every login form gets the widget, and the
|                     ones nobody needs it on still render it — harmless, since
|                     nothing is verified there.
|
|   - requiredFor():  does this sign-in have to answer it? Judged against the
|                     account's own roles, so switching a role on never starts
|                     demanding a token from someone it was not switched on for.
|
| Every door is checked separately: they are four pieces of code, and three of
| them worked before this and would still have "worked" while demanding a captcha
| from accounts that had no way to pass it.
|
*/

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Delivery\Auth\Login as DeliveryLogin;
use App\Livewire\Vendor\Auth\Login as VendorLogin;
use App\Models\ProductVendor;
use App\Models\User;
use App\Support\Recaptcha;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    // Configured, but no role asking for one yet — the state an install is in
    // between having keys and having decided to use them.
    config([
        'services.recaptcha.site_key' => 'site-key-for-tests',
        'services.recaptcha.secret_key' => 'secret-key-for-tests',
    ]);
});

/**
 * A vendor whose account exists and can sign in.
 *
 * Activated here rather than at each call site: the seeder creates the portal
 * roles inactive, and an inactive role rejects its holders at login — which would
 * look like a captcha failure if it happened before the password was checked.
 */
function captchaVendor(): User
{
    activateRoles('vendor');

    $user = User::factory()->create(['password' => 'password']);

    $user->assignRole('vendor');
    ProductVendor::factory()->create()->users()->attach($user);

    return $user;
}

/** A rider whose account exists and can sign in. */
function captchaRider(): User
{
    activateRoles('delivery_boy');

    $user = User::factory()->create(['password' => 'password']);

    $user->assignRole('delivery_boy');

    return $user;
}

// ── Is the widget worth rendering? ─────────────────────────────────────────

it('renders no widget while no role asks for one', function () {
    expect(Recaptcha::enabled())->toBeFalse();
});

it('renders the widget as soon as one role asks for one', function () {
    Role::where('name', 'vendor')->update(['recaptcha_enabled' => true]);

    expect(Recaptcha::enabled())->toBeTrue();
});

it('renders nothing when a role asks but the keys are missing', function () {
    // A widget whose secret is unset can never verify anything, so showing it
    // would block every login on that role with no way past the block.
    Role::where('name', 'vendor')->update(['recaptcha_enabled' => true]);

    config(['services.recaptcha.secret_key' => null]);

    expect(Recaptcha::enabled())->toBeFalse();
});

it('renders nothing when the keys are set but no role asks', function () {
    // The other half of the same condition: keys alone must not put a captcha in
    // front of everyone.
    expect(Recaptcha::enabled())->toBeFalse();
});

// ── Does this sign-in have to answer it? ───────────────────────────────────

it('demands nothing from an account whose role does not ask', function () {
    $rider = captchaRider();

    Role::where('name', 'vendor')->update(['recaptcha_enabled' => true]);

    expect(Recaptcha::requiredFor($rider))->toBeFalse();
});

it('demands the captcha from an account whose own role does ask', function () {
    $vendor = captchaVendor();

    Role::where('name', 'vendor')->update(['recaptcha_enabled' => true]);

    expect(Recaptcha::requiredFor($vendor))->toBeTrue();
});

it('demands nothing when there is no account behind the address', function () {
    // Checked before the password on purpose: asking a stranger to solve a
    // captcha before telling them the password is wrong would be both a worse
    // error and a way of testing which addresses are real.
    Role::where('name', 'vendor')->update(['recaptcha_enabled' => true]);

    expect(Recaptcha::requiredFor(User::where('email', 'nobody@example.test')->first()))->toBeFalse();
});

it('demands nothing when a role asks but the secret is unset', function () {
    $vendor = captchaVendor();

    Role::where('name', 'vendor')->update(['recaptcha_enabled' => true]);

    config(['services.recaptcha.secret_key' => null]);

    expect(Recaptcha::requiredFor($vendor))->toBeFalse();
});

// ── The four doors ─────────────────────────────────────────────────────────

it('the admin login refuses a password with no captcha token', function () {
    $admin = User::factory()->admin()->create(['password' => 'password']);

    Role::where('name', 'admin')->update(['recaptcha_enabled' => true]);

    Livewire::test(AdminLogin::class)
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('recaptchaToken');

    expect(auth()->check())->toBeFalse();
});

it('the vendor login refuses a password with no captcha token', function () {
    $vendor = captchaVendor();

    Role::where('name', 'vendor')->update(['recaptcha_enabled' => true]);

    Livewire::test(VendorLogin::class)
        ->set('email', $vendor->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('recaptchaToken');

    expect(auth()->check())->toBeFalse();
});

it('the delivery login refuses a password with no captcha token', function () {
    $rider = captchaRider();

    Role::where('name', 'delivery_boy')->update(['recaptcha_enabled' => true]);

    Livewire::test(DeliveryLogin::class)
        ->set('email', $rider->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('recaptchaToken');

    expect(auth()->check())->toBeFalse();
});

it('the customer login refuses a password with no captcha token', function () {
    $user = User::factory()->create(['password' => 'password']);

    $user->assignRole('customer');
    activateRoles('customer');

    Role::where('name', 'customer')->update(['recaptcha_enabled' => true]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        // The storefront form posts the widget's own field name rather than a
        // Livewire property — Fortify validates the request, not a component.
    ])->assertSessionHasErrors('g-recaptcha-response');

    expect(auth()->check())->toBeFalse();
});

it('a door whose role has no switch does not ask for a token', function () {
    // The other half of the per-role split: switching one role on must not start
    // demanding a captcha from every other door, or an admin who turns reCAPTCHA
    // on for their own role locks out everyone who never asked for it.
    $vendor = captchaVendor();

    Role::where('name', 'admin')->update(['recaptcha_enabled' => true]);

    Livewire::test(VendorLogin::class)
        ->set('email', $vendor->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors();

    expect(auth()->check())->toBeTrue();
});

it('never asks an address with no account behind it', function () {
    // Asked ahead of the password check on purpose, so the interesting question
    // is what a stranger at a fake address gets. Nothing: they are told the
    // credentials are wrong, exactly as they would be if the address were real.
    // Asking a stranger to solve a captcha first would answer "does this address
    // exist, and does it want a captcha?" in two separate error messages.
    Role::where('name', 'vendor')->update(['recaptcha_enabled' => true]);

    Livewire::test(VendorLogin::class)
        ->set('email', 'nobody@example.test')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email')
        ->assertHasNoErrors('recaptchaToken');
});

it('every login form renders the widget once any role asks, since the account is unknown', function () {
    Role::where('name', 'vendor')->update(['recaptcha_enabled' => true]);

    $this->get('http://'.config('app.admin_host').'/login')
        ->assertOk()
        ->assertSee('g-recaptcha-response', false);

    $this->get('http://'.config('app.vendor_host').'/login')
        ->assertOk()
        ->assertSee('g-recaptcha-response', false);

    $this->get('http://'.config('app.delivery_host').'/login')
        ->assertOk()
        ->assertSee('g-recaptcha-response', false);
});
