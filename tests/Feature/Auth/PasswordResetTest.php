<?php

use App\Mail\PasswordResetOtpMail;
use App\Models\Setting;
use App\Models\User;
use App\Providers\FortifyServiceProvider;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Password reset by emailed code.
 *
 * Three steps, in order: ask for a code on the address, redeem it, set a new
 * password. The code is what proves the requester can read that inbox, and
 * redeeming it leaves a session flag that the last step reads the address from �?"
 * so the address cannot be swapped between the two.
 *
 * Replaces Fortify's token link, which pointed at a page in the Next.js app and
 * so was a dead end until that page existed. See App\Services\PasswordResetService.
 *
 * The three screens are theme templates (themes/ecommerce/auth/{forgot-password,
 * verify-code,reset-password}), so the theme is pinned to the one bundled theme
 * that ships them \u2014 a theme without an auth/ folder has no customer account to
 * reset, and 404s the whole flow (see CustomerAccountTest).
 */
beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');
});

/** The code that was actually mailed, read back off the fake mail. */
function mailedCode(): string
{
    $code = null;

    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    return (string) $code;
}

/** Asks for a code and returns it, as though the customer had opened their inbox. */
function requestCodeFor(User $user): string
{
    Mail::fake();

    test()->post(route('password.email'), ['email' => $user->email])->assertRedirect(route('password.verify'));

    return mailedCode();
}

it('shows the forgot password form', function () {
    $this->get(route('password.request'))->assertOk();
});

it('emails a code to the address given', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.verify'))
        ->assertSessionHasNoErrors();

    Mail::assertSent(PasswordResetOtpMail::class, fn (PasswordResetOtpMail $mail) => $mail->hasTo($user->email));
});

it('answers the same way for an address with no account, and mails nothing', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertRedirect(route('password.verify'));
    $this->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect(route('password.verify'))
        ->assertSessionHasNoErrors();

    // Saying "no such account" would turn this form into a way of finding out
    // who shops here. The only cost is that a mistyped address waits for a code
    // that never comes.
    Mail::assertSent(PasswordResetOtpMail::class, 1);
});

it('sends the customer on to enter the code they were sent', function () {
    $user = User::factory()->create();

    $this->get(route('password.verify'))->assertRedirect(route('password.request'));

    requestCodeFor($user);

    $this->get(route('password.verify'))
        ->assertOk()
        ->assertSee($user->email);
});

it('rejects a wrong code and keeps the real one usable', function () {
    $user = User::factory()->create();
    $code = requestCodeFor($user);

    $this->post(route('password.verify.check'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->get(route('password.reset'))->assertRedirect(route('password.request'));

    $this->post(route('password.verify.check'), ['code' => $code])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHasNoErrors();
});

it('sets a new password once the code is redeemed, and the customer can sign in with it', function () {
    $user = User::factory()->create(['password' => 'the-old-password']);
    $code = requestCodeFor($user);

    $this->post(route('password.verify.check'), ['code' => $code]);

    $this->post(route('password.update'), [
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHasNoErrors();

    expect(Hash::check('a-brand-new-password', $user->fresh()->password))->toBeTrue()
        ->and(Hash::check('the-old-password', $user->fresh()->password))->toBeFalse();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'a-brand-new-password'])
        ->assertRedirect();
});

it('ignores an address named in the request, changing only the account the code was for', function () {
    $user = User::factory()->create(['password' => 'the-old-password']);
    $attacker = User::factory()->create(['email' => 'attacker@example.com', 'password' => 'attacker-password']);
    $code = requestCodeFor($user);

    $this->post(route('password.verify.check'), ['code' => $code]);

    // Even with the victim's verified session, the address the form happens to
    // carry is never read. The account that changes is the one behind the code.
    $this->post(route('password.update'), [
        'email' => 'attacker@example.com',
        'password' => 'attacker-chosen',
        'password_confirmation' => 'attacker-chosen',
    ])->assertRedirect(route('login'));

    expect(Hash::check('attacker-password', $attacker->fresh()->password))->toBeTrue()
        ->and(Hash::check('attacker-chosen', $user->fresh()->password))->toBeTrue();
});

it('refuses to show or submit the password form without a redeemed code', function () {
    $this->get(route('password.reset'))->assertRedirect(route('password.request'));

    $this->post(route('password.update'), [
        'password' => 'never-verified',
        'password_confirmation' => 'never-verified',
    ])->assertRedirect(route('password.request'));
});

it('rejects a password that does not meet the rules, without spending anything', function () {
    $user = User::factory()->create(['password' => 'the-old-password']);
    $code = requestCodeFor($user);

    $this->post(route('password.verify.check'), ['code' => $code]);

    $this->post(route('password.update'), [
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('the-old-password', $user->fresh()->password))->toBeTrue();
});

it('will not accept the same code twice', function () {
    $user = User::factory()->create(['password' => 'the-old-password']);
    $code = requestCodeFor($user);

    $this->post(route('password.verify.check'), ['code' => $code])->assertRedirect(route('password.reset'));
    $this->post(route('password.update'), [
        'password' => 'first-new-password',
        'password_confirmation' => 'first-new-password',
    ]);

    // A second attempt from scratch with the code that was already spent.
    $this->post(route('password.email'), ['email' => $user->email]);
    $this->post(route('password.verify.check'), ['code' => $code])->assertSessionHasErrors('code');

    $this->get(route('password.reset'))->assertRedirect(route('password.request'));
});

it('tells the customer when the code has expired and sends them back for a new one', function () {
    $user = User::factory()->create();
    $code = requestCodeFor($user);

    $this->travel(11)->minutes();

    $this->post(route('password.verify.check'), ['code' => $code])->assertSessionHasErrors('code');

    $this->get(route('password.verify'))->assertRedirect(route('password.request'));
});

it('lets the code already in the inbox be used when another is asked for straight away', function () {
    Mail::fake();
    $user = User::factory()->create(['password' => 'the-old-password']);

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
    $code = mailedCode();

    // The customer who just ordered as a guest lands here with a claim code
    // already waiting. Being told to come back in a minute, while holding a
    // code that works, would be pure friction.
    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.verify'))
        ->assertSessionHasNoErrors();

    // No second code went out, so the one in the inbox is still the real one.
    Mail::assertSent(PasswordResetOtpMail::class, 1);

    $this->post(route('password.verify.check'), ['code' => $code])->assertRedirect(route('password.reset'));
    $this->post(route('password.update'), [
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])->assertRedirect(route('login'));

    expect(Hash::check('a-brand-new-password', $user->fresh()->password))->toBeTrue();
});

it('stops sending after the hour\'s worth, and says so', function () {
    Mail::fake();
    $user = User::factory()->create();

    // Spaced past the resend cooldown, which is what someone trying to fill an
    // inbox would do — a burst is turned away by the cooldown and the per-minute
    // throttle long before this bites.
    foreach (range(1, OtpService::MAX_ISSUES_PER_HOUR) as $_) {
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
    }

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('email');

    Mail::assertSent(PasswordResetOtpMail::class, OtpService::MAX_ISSUES_PER_HOUR);
});

it('stops answering an address with no account just the same', function () {
    Mail::fake();
    $user = User::factory()->create();

    // Both addresses are asked for more often than the limit allows, and both
    // are refused from the same request onward. Were the limit spent only by
    // real sends, the address that stopped being answered would be the one with
    // an account — turning this form into a way of finding out who shops here,
    // at a cost of a handful of emails per probe.
    foreach (range(1, OtpService::MAX_ISSUES_PER_HOUR) as $_) {
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
        $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertSessionHasNoErrors();
        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
    }

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('email');
    $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertSessionHasErrors('email');

    Mail::assertSent(PasswordResetOtpMail::class, OtpService::MAX_ISSUES_PER_HOUR);
});

it('turns away a script at the address+IP limit, without mailing anything', function () {
    Mail::fake();
    $user = User::factory()->create();

    // The route's own limit, counting requests rather than codes, and so needing
    // no waiting between attempts. It is what a script hits first.
    foreach (range(1, FortifyServiceProvider::OTP_MAX_REQUESTS) as $_) {
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
    }

    $this->post(route('password.email'), ['email' => $user->email])->assertStatus(429);

    // Those requests all shared the single code the cooldown allowed.
    Mail::assertSent(PasswordResetOtpMail::class, 1);
});

it('goes on refusing the same address from every other address', function () {
    Mail::fake();
    $user = User::factory()->create();

    // The route limit is keyed on the address *and* the IP, so an attacker with
    // a pool of addresses walks past it — one fresh allowance per pair, none of
    // them shared. What still holds is the quota, which counts against the
    // address itself, so the mailbox stops filling after a handful of codes no
    // matter where the requests appear to come from.
    foreach (range(1, OtpService::MAX_ISSUES_PER_HOUR) as $_) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.$_])
            ->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasErrors('email');

    Mail::assertSent(PasswordResetOtpMail::class, OtpService::MAX_ISSUES_PER_HOUR);
});

it('does not let one address lock out another behind the same IP', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $neighbour = User::factory()->create(['email' => 'neighbour@example.com']);

    // An office, a café, a mobile carrier: plenty of unrelated people behind one
    // address. Keyed on the pair rather than the IP alone, so one of them trying
    // to get in does not lock out the rest.
    foreach (range(1, FortifyServiceProvider::OTP_MAX_REQUESTS) as $_) {
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
    }

    $this->post(route('password.email'), ['email' => $user->email])->assertStatus(429);
    $this->post(route('password.email'), ['email' => $neighbour->email])->assertSessionHasNoErrors();
});

it('lets a customer back in once the hour has passed', function () {
    Mail::fake();
    $user = User::factory()->create();

    foreach (range(1, FortifyServiceProvider::OTP_MAX_REQUESTS) as $_) {
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
    }

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('email');

    $this->travel(OtpService::QUOTA_HOURS + 1)->hours();

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
});

it('requires an address to ask for a code with', function () {
    $this->post(route('password.email'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

it('matches the code however the customer typed the address', function () {
    $user = User::factory()->create(['email' => 'jane@example.com', 'password' => 'the-old-password']);

    Mail::fake();
    $this->post(route('password.email'), ['email' => 'Jane@Example.COM'])->assertRedirect(route('password.verify'));

    $this->post(route('password.verify.check'), ['code' => mailedCode()])->assertRedirect(route('password.reset'));
    $this->post(route('password.update'), [
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])->assertRedirect(route('login'));

    expect(Hash::check('a-brand-new-password', $user->fresh()->password))->toBeTrue();
});

it('is not offered to somebody already signed in', function () {
    $this->actingAs(User::factory()->create());

    // A signed-in customer has no password to forget, and a fresh code would be a
    // way to change the password of an account somebody is already using.
    $this->get(route('password.request'))->assertRedirect();
    $this->post(route('password.email'), ['email' => 'someone@example.com'])->assertRedirect();
});
