<?php

/*
|--------------------------------------------------------------------------
| MFA policy, enforcement and challenge
|--------------------------------------------------------------------------
|
| Three things are being proved here, and they are deliberately kept apart
| because they fail for different reasons:
|
|   1. What Mfa answers. A confirmed secret counts, an unconfirmed one does not,
|      and either kind of factor counts. This is the question every other file
|      delegates to, so it is worth pinning on its own.
|
|   2. That enforcement is a dead end rather than a wall. A policy on with no
|      factor has to land the user somewhere they can *fix* it — the enrolment
|      screen and the profile both have to answer while the middleware holds,
|      or switching the policy on locks an admin out of their own panel with no
|      way to recover.
|
|   3. That all four login doors challenge. The three portals have a login each
|      and Fortify has the fourth; each is a separate piece of code and each was
|      a separate bypass before this.
|
|   4. Where the policy lives. It is a switch on the role, not a global setting:
|      "require MFA" means nothing until you can say require it of *whom*, and a
|      single setting covering four doors was why it was moved.
|
*/

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Delivery\Auth\Login as DeliveryLogin;
use App\Livewire\Vendor\Auth\Login as VendorLogin;
use App\Models\ProductVendor;
use App\Models\Setting;
use App\Models\User;
use App\Support\Mfa;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * Switch MFA on for the roles behind an audience.
 *
 * A direct column write, where the old version went through Setting::set() — the
 * policy moved onto the role, and the forever cache that made Setting::set()
 * necessary was the cache of a Setting row. Nothing here is cached any more.
 *
 * Takes an audience rather than a role name because the tests are about doors:
 * "admin" and "staff" share one door, and neither role name says so.
 */
function requireMfaFor(string $audience): void
{
    Role::query()
        ->whereIn('name', match ($audience) {
            Mfa::AUDIENCE_ADMIN => ['admin', 'staff'],
            Mfa::AUDIENCE_VENDOR => ['vendor'],
            Mfa::AUDIENCE_DELIVERY => ['delivery_boy'],
        })
        ->update(['mfa_enabled' => true]);
}

/** An admin with a confirmed TOTP secret. */
function adminWithTotp(): User
{
    return User::factory()->admin()->withTwoFactor()->create();
}

/** An admin holding only a passkey and no TOTP secret at all. */
function adminWithPasskeyOnly(): User
{
    $user = User::factory()->admin()->create();

    $user->passkeys()->create([
        'name' => 'Test key',
        'credential_id' => 'test-credential-id',
        'credential' => ['type' => 'public-key'],
        'user_handle' => 'test-user-handle',
        'attestation_type' => 'none',
    ]);

    return $user->fresh();
}

// ── 1. What Mfa answers ──────────────────────────────────────────────────

test('a confirmed secret counts as a factor and an unconfirmed one does not', function () {
    $user = User::factory()->create();

    expect(Mfa::totpEnabledFor($user))->toBeFalse()
        ->and(Mfa::hasFactorFor($user))->toBeFalse();

    // The state an abandoned enrolment leaves behind: a secret written, a QR the
    // user walked away from. It must not read as "MFA is on".
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => null,
    ])->save();

    expect(Mfa::totpEnabledFor($user->fresh()))->toBeFalse()
        ->and(Mfa::hasFactorFor($user->fresh()))->toBeFalse();

    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    expect(Mfa::totpEnabledFor($user->fresh()))->toBeTrue()
        ->and(Mfa::hasFactorFor($user->fresh()))->toBeTrue();
});

test('a passkey alone counts as a factor', function () {
    $user = adminWithPasskeyOnly();

    expect($user->two_factor_secret)->toBeNull()
        ->and(Mfa::totpEnabledFor($user))->toBeFalse()
        ->and(Mfa::hasPasskeyFor($user))->toBeTrue()
        // The interchangeability this whole design rests on: the challenge page
        // is built around TOTP, so a passkey-only account would be stuck without
        // this being true.
        ->and(Mfa::hasFactorFor($user))->toBeTrue();
});

test('the policy is per audience, and a plain customer is in none of them', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    requireMfaFor(Mfa::AUDIENCE_ADMIN);

    expect(Mfa::isRequiredFor($admin))->toBeTrue()
        ->and(Mfa::isRequiredFor($customer))->toBeFalse()
        ->and(Mfa::audiencesFor($admin))->toBe([Mfa::AUDIENCE_ADMIN])
        ->and(Mfa::audiencesFor($customer))->toBe([]);
});

// ── 2. Enforcement is a dead end, not a wall ──────────────────────────────

test('enforcement is off by default so an upgrade cannot lock the panel', function () {
    $admin = User::factory()->admin()->create();

    expect(Mfa::isRequiredFor($admin))->toBeFalse();

    $this->actingAs($admin)
        ->get('http://'.config('app.admin_host').'/')
        ->assertOk();
});

test('an admin with no factor is held at the enrolment screen, and the screen answers', function () {
    $admin = User::factory()->admin()->create();

    requireMfaFor(Mfa::AUDIENCE_ADMIN);

    $host = config('app.admin_host');

    // Held — the dashboard does not answer.
    $this->actingAs($admin)
        ->get('http://'.$host.'/')
        ->assertRedirect(route('admin.mfa.required'));

    // And the screen it is held at does, or the policy would be unsatisfiable.
    $this->actingAs($admin)
        ->get('http://'.$host.'/mfa-required')
        ->assertOk()
        ->assertSee('Set one up below to continue', false);
});

test('the profile answers while enforcement is on, because that is where the panel lives', function () {
    $admin = User::factory()->admin()->create();

    requireMfaFor(Mfa::AUDIENCE_ADMIN);

    $this->actingAs($admin)
        ->get('http://'.config('app.admin_host').'/profile')
        ->assertOk();
});

test('a compliant admin is let through', function () {
    requireMfaFor(Mfa::AUDIENCE_ADMIN);

    $this->actingAs(adminWithTotp())
        ->get('http://'.config('app.admin_host').'/')
        ->assertOk();
});

test('a passkey-only admin is let through too', function () {
    $this->skipUnlessFortifyHas(Features::passkeys());

    requireMfaFor(Mfa::AUDIENCE_ADMIN);

    $this->actingAs(adminWithPasskeyOnly())
        ->get('http://'.config('app.admin_host').'/')
        ->assertOk();
});

test('a vendor is held on the vendor host, not the admin one', function () {
    activateRoles('vendor');

    $user = User::factory()->create();
    $user->assignRole('vendor');
    ProductVendor::factory()->create()->users()->attach($user);

    requireMfaFor(Mfa::AUDIENCE_VENDOR);

    $host = config('app.vendor_host');

    $this->actingAs($user)
        ->get('http://'.$host.'/')
        ->assertRedirect(route('vendor.mfa.required'));

    $this->actingAs($user)
        ->get('http://'.$host.'/mfa-required')
        ->assertOk();
});

test('a policy for one audience does not hold a different portal', function () {
    activateRoles('delivery_boy');

    $user = User::factory()->create();
    $user->assignRole('delivery_boy');

    requireMfaFor(Mfa::AUDIENCE_VENDOR);

    $this->actingAs($user)
        ->get('http://'.config('app.delivery_host').'/')
        ->assertOk();
});

// ── 3. Every login door challenges ────────────────────────────────────────

test('the admin login sends a user with a factor to the challenge', function () {
    requireMfaFor(Mfa::AUDIENCE_ADMIN);

    $admin = adminWithTotp();

    Livewire::test(AdminLogin::class)
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        // On the panel's own host, not the main site: the login.id this
        // challenge finishes is in a host-only session, so a redirect to
        // APP_URL would strand the sign-in with nothing to answer it.
        ->assertRedirect(Mfa::challengeUrl(Mfa::AUDIENCE_ADMIN));

    // Parked, not signed in: the password half alone must not open the panel.
    expect(auth()->check())->toBeFalse()
        ->and(session('login.id'))->toBe($admin->id);
});

test('the vendor login sends a user with a factor to the challenge', function () {
    activateRoles('vendor');

    requireMfaFor(Mfa::AUDIENCE_VENDOR);

    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole('vendor');
    ProductVendor::factory()->create()->users()->attach($user);

    Livewire::test(VendorLogin::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(Mfa::challengeUrl(Mfa::AUDIENCE_VENDOR));

    // Auth::attempt had already logged them in, so the challenge redirect is
    // only safe if the session was dropped again on the way out.
    expect(auth()->check())->toBeFalse()
        ->and(session('login.id'))->toBe($user->id);
});

test('the delivery login sends a user with a factor to the challenge', function () {
    activateRoles('delivery_boy');

    requireMfaFor(Mfa::AUDIENCE_DELIVERY);

    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole('delivery_boy');

    Livewire::test(DeliveryLogin::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(Mfa::challengeUrl(Mfa::AUDIENCE_DELIVERY));

    expect(auth()->check())->toBeFalse()
        ->and(session('login.id'))->toBe($user->id);
});

test('a user with no factor signs in normally and is held later, not at the login', function () {
    $admin = User::factory()->admin()->create();

    Livewire::test(AdminLogin::class)
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    expect(session('login.id'))->toBeNull();
});

test('Fortify own login challenges a passkey-only account', function () {
    $this->skipUnlessFortifyHas(Features::passkeys());

    $user = User::factory()->create([
        'password' => 'password',
        'two_factor_secret' => null,
    ]);

    $user->assignRole('customer');
    activateRoles('customer');

    Role::where('name', 'customer')->update(['mfa_enabled' => true]);

    $user->passkeys()->create([
        'name' => 'Test key',
        'credential_id' => 'fortify-credential-id',
        'credential' => ['type' => 'public-key'],
        'user_handle' => 'fortify-user-handle',
        'attestation_type' => 'none',
    ]);

    // Fortify's own action only reads two_factor_secret, so without the swap in
    // FortifyServiceProvider this account walks straight in.
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    expect(auth()->check())->toBeFalse();
});

// ── The passkey challenge endpoints ───────────────────────────────────────

test('the passkey challenge options bounce to login with no half-finished login behind them', function () {
    $this->get(route('two-factor.passkey-options'))
        ->assertRedirect(route('login'));
});

test('the passkey challenge refuses when the challenged account has no passkey', function () {
    $admin = adminWithTotp();

    $this->withSession(['login.id' => $admin->id])
        ->getJson(route('two-factor.passkey-options'))
        ->assertStatus(422)
        ->assertJsonValidationErrors('passkey');
});

test('the passkey challenge offers itself to an account that has one', function () {
    $this->skipUnlessFortifyHas(Features::passkeys());

    $user = adminWithPasskeyOnly();

    // The page asks App\Support\Mfa, not the package, whether to show the button.
    $this->withSession(['login.id' => $user->id])
        ->get(route('two-factor.login'))
        ->assertOk()
        ->assertSee('Use a passkey');
});

test('an account with no passkey sees the challenge page exactly as before', function () {
    $admin = adminWithTotp();

    $this->withSession(['login.id' => $admin->id])
        ->get(route('two-factor.login'))
        ->assertOk()
        ->assertDontSee('Use a passkey');
});

// ── Where the policy lives: the role, not a setting ────────────────────────

test('no role asks for MFA until somebody asks for it', function () {
    foreach (Role::all() as $role) {
        expect((bool) $role->mfa_enabled)->toBeFalse();
    }
});

test('a role with MFA on requires it of its holders and of nobody else', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Role::where('name', 'admin')->update(['mfa_enabled' => true]);

    expect(Mfa::isRequiredFor($admin))->toBeTrue()
        ->and(Mfa::isRequiredFor($customer))->toBeFalse();
});

test('staff share the admin door, so switching one switches both', function () {
    Role::whereIn('name', ['admin', 'staff'])->update(['mfa_enabled' => true]);

    $staff = User::factory()->create();
    $staff->assignRole('staff');

    expect(Mfa::isRequiredFor($staff))->toBeTrue();
});

test('one role of several is enough to require a factor', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $user->assignRole('vendor');

    expect(Mfa::isRequiredFor($user))->toBeFalse();

    Role::where('name', 'vendor')->update(['mfa_enabled' => true]);

    expect(Mfa::isRequiredFor($user))->toBeTrue();
});

test('the Roles screen is where the switch lives, so Settings no longer carries one', function () {
    $html = Livewire::test(RolesIndex::class)->html();

    expect($html)->toContain('MFA', 'reCAPTCHA');

    // The Settings page keeps no policy switches at all: the whole reason this
    // moved is that one global switch could not say who it applied to.
    $settingsHtml = Livewire::test(SettingsIndex::class)->html();

    expect($settingsHtml)->not->toContain('Multi-factor authentication')
        ->and(Setting::query()->whereIn('key', [
            'mfa_require_admin',
            'mfa_require_vendor',
            'mfa_require_delivery',
            'mfa_protect_api',
            'recaptcha_enabled',
        ])->exists())->toBeFalse();
});

test('the security group is kept out of the generic settings groups', function () {
    // Even with nothing seeding that group any more, a row left behind on an
    // older database must not reappear here as a bare unexplained checkbox.
    Setting::set('mfa_require_admin', '1', 'boolean', 'security');

    Livewire::test(SettingsIndex::class)
        ->assertViewHas('groupedSettings', fn ($groups) => ! $groups->has('security'));
});
