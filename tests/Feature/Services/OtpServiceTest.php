<?php

namespace tests\Feature\Services;

use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * The code mechanics every emailed-code flow rests on — the password reset
 * (App\Services\PasswordResetService) and the delivery confirmation both lean on
 * these, so they are tested here once rather than through whichever caller
 * happens to be exercising them.
 */
beforeEach(function () {
    $this->otp = new OtpService;
});

/** The code that would have been mailed, captured from the fake send callback. */
function issueOtp(string $email, string $purpose = 'test-purpose'): string
{
    $code = null;

    app(OtpService::class)->issue($email, $purpose, function (string $issued) use (&$code) {
        $code = $issued;
    });

    return (string) $code;
}

it('mails a six digit code and accepts it once', function () {
    $code = issueOtp('jane@example.com');

    expect($code)->toHaveLength(6)
        ->and($code)->toMatch('/^\d{6}$/')
        ->and(app(OtpService::class)->verify('jane@example.com', 'test-purpose', $code))->toBe(OtpService::VERIFIED);
});

it('stores the code hashed rather than in the clear', function () {
    $code = issueOtp('jane@example.com');

    $stored = Cache::get('otp:test-purpose:jane@example.com:code');

    // A leaked cache row — a shared Redis, a cache dump — must not be a set of
    // working codes, so what lands there has to be a hash of the code.
    expect($stored)->not->toBe($code)
        ->and(Hash::check($code, $stored))->toBeTrue();
});

it('rejects a wrong code without spending the real one', function () {
    $code = issueOtp('jane@example.com');

    expect(app(OtpService::class)->verify('jane@example.com', 'test-purpose', '000000'))->toBe(OtpService::INVALID)
        ->and(app(OtpService::class)->verify('jane@example.com', 'test-purpose', $code))->toBe(OtpService::VERIFIED);
});

it('burns the code after a handful of wrong guesses, forcing a new one', function () {
    $code = issueOtp('jane@example.com');
    $otp = app(OtpService::class);

    // A six digit code is a million candidates. Without a cap, a caller could
    // simply keep guessing until one lands.
    foreach (range(1, OtpService::MAX_ATTEMPTS - 1) as $_) {
        expect($otp->verify('jane@example.com', 'test-purpose', '000000'))->toBe(OtpService::INVALID);
    }

    expect($otp->verify('jane@example.com', 'test-purpose', '000000'))->toBe(OtpService::LOCKED)
        ->and($otp->verify('jane@example.com', 'test-purpose', $code))->toBe(OtpService::EXPIRED);
});

it('spends the code on a successful verification', function () {
    $code = issueOtp('jane@example.com');
    $otp = app(OtpService::class);

    $otp->verify('jane@example.com', 'test-purpose', $code);

    // One use only: a code that stayed valid could be replayed by anything that
    // saw it, and anything that saw it has already proven it can read the inbox.
    expect($otp->verify('jane@example.com', 'test-purpose', $code))->toBe(OtpService::EXPIRED);
});

it('expires the code on its own', function () {
    $code = issueOtp('jane@example.com');

    $this->travel(OtpService::TTL_MINUTES + 1)->minutes();

    expect(app(OtpService::class)->verify('jane@example.com', 'test-purpose', $code))->toBe(OtpService::EXPIRED);
});

it('refuses to issue a second code while the resend cooldown is running', function () {
    issueOtp('jane@example.com');

    expect(app(OtpService::class)->issue('jane@example.com', 'test-purpose', fn () => null))->toBe(OtpService::THROTTLED);
});

it('issues again once the cooldown has passed, and the new code replaces the old', function () {
    $first = issueOtp('jane@example.com');

    $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

    $second = null;
    $status = app(OtpService::class)->issue('jane@example.com', 'test-purpose', function (string $code) use (&$second) {
        $second = $code;
    });

    $otp = app(OtpService::class);

    expect($status)->toBe(OtpService::SENT)
        // Only one code can be live at a time, or a customer who has the older
        // one open while a newer one arrives would be told theirs was wrong.
        ->and($otp->verify('jane@example.com', 'test-purpose', $first))->toBe(OtpService::INVALID)
        ->and($otp->verify('jane@example.com', 'test-purpose', $second))->toBe(OtpService::VERIFIED);
});

it('caches nothing at all when the mail cannot be sent', function () {
    $otp = app(OtpService::class);

    $status = $otp->issue('jane@example.com', 'test-purpose', function () {
        throw new \RuntimeException('SMTP connection refused');
    });

    // A code nobody received must not be treated as a live one — otherwise a
    // caller is told their mail is on the way when it never left.
    expect($status)->toBe(OtpService::FAILED)
        ->and($otp->verify('jane@example.com', 'test-purpose', '123456'))->toBe(OtpService::EXPIRED)
        ->and(Cache::has('otp:test-purpose:jane@example.com:cooldown'))->toBeFalse();
});

it('stops sending once an address has had its hour\'s worth of codes', function () {
    $otp = app(OtpService::class);

    // The cooldown would otherwise allow a code every minute, forever, which is
    // a hundred and forty-four hundred emails a day aimed at one inbox.
    for ($i = 0; $i < OtpService::MAX_ISSUES_PER_HOUR; $i++) {
        expect($otp->issue('jane@example.com', 'test-purpose', fn () => null))->toBe(OtpService::SENT);

        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
    }

    expect($otp->issue('jane@example.com', 'test-purpose', fn () => null))->toBe(OtpService::RATE_LIMITED);
});

it('counts the quota against the address, not the cooldown', function () {
    $otp = app(OtpService::class);

    // Leaning on the resend button must not cost any of the hourly allowance,
    // or a customer fumbling an address twice would be locked out for an hour on
    // a technicality.
    for ($i = 0; $i < OtpService::MAX_ISSUES_PER_HOUR * 3; $i++) {
        $otp->issue('jane@example.com', 'test-purpose', fn () => null);
    }

    expect(Cache::get('otp:test-purpose:jane@example.com:quota'))->toBe(1);

    $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

    expect($otp->issue('jane@example.com', 'test-purpose', fn () => null))->toBe(OtpService::SENT);
});

it('lets the quota refill once the hour is up', function () {
    $otp = app(OtpService::class);

    foreach (range(1, OtpService::MAX_ISSUES_PER_HOUR) as $_) {
        $otp->issue('jane@example.com', 'test-purpose', fn () => null);
        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
    }

    expect($otp->issue('jane@example.com', 'test-purpose', fn () => null))->toBe(OtpService::RATE_LIMITED);

    $this->travel(OtpService::QUOTA_HOURS + 1)->hours();

    expect($otp->issue('jane@example.com', 'test-purpose', fn () => null))->toBe(OtpService::SENT);
});

it('keeps one address\'s quota out of another\'s', function () {
    $otp = app(OtpService::class);

    // Otherwise one customer leaning on the button locks out their household.
    foreach (range(1, OtpService::MAX_ISSUES_PER_HOUR + 1) as $_) {
        $otp->issue('jane@example.com', 'test-purpose', fn () => null);
        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
    }

    expect($otp->issue('jane@example.com', 'test-purpose', fn () => null))->toBe(OtpService::RATE_LIMITED)
        ->and($otp->issue('john@example.com', 'test-purpose', fn () => null))->toBe(OtpService::SENT);
});

it('keeps one purpose\'s quota out of another\'s', function () {
    $otp = app(OtpService::class);

    foreach (range(1, OtpService::MAX_ISSUES_PER_HOUR + 1) as $_) {
        $otp->issue('jane@example.com', 'password-reset', fn () => null);
        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
    }

    // Exhausting the reset allowance is no reason to stop a delivery code going
    // out, and the other way round.
    expect($otp->issue('jane@example.com', 'password-reset', fn () => null))->toBe(OtpService::RATE_LIMITED)
        ->and($otp->issue('jane@example.com', 'delivery', fn () => null))->toBe(OtpService::SENT);
});

it('spends the quota on a request that sends nothing, so the two are indistinguishable', function () {
    $otp = app(OtpService::class);

    // PasswordResetService calls this for addresses with no account too. If only
    // real sends counted, "keep asking until you're told to stop" would answer
    // whether the account exists.
    foreach (range(1, OtpService::MAX_ISSUES_PER_HOUR) as $_) {
        expect($otp->claimQuota('nobody@example.com', 'test-purpose'))->toBeTrue();
    }

    expect($otp->claimQuota('nobody@example.com', 'test-purpose'))->toBeFalse();
});

it('does not spend the quota on a mail that failed to go out', function () {
    $otp = app(OtpService::class);

    // An outage on our side must not cost the customer their allowance — they
    // are the one waiting, and nothing was delivered to anyone.
    foreach (range(1, OtpService::MAX_ISSUES_PER_HOUR + 2) as $_) {
        $otp->issue('jane@example.com', 'test-purpose', fn () => throw new \RuntimeException('SMTP connection refused'));
    }

    expect(Cache::get('otp:test-purpose:jane@example.com:quota'))->toBe(OtpService::MAX_ISSUES_PER_HOUR + 2);
});

it('keeps codes for different purposes apart', function () {
    $reset = issueOtp('jane@example.com', 'password-reset');
    $delivery = issueOtp('jane@example.com', 'delivery');

    $otp = app(OtpService::class);

    // Redeeming one flow's code must not spend another's, or a customer
    // confirming a delivery could be logged into their own account.
    expect($otp->verify('jane@example.com', 'delivery', $reset))->toBe(OtpService::INVALID)
        ->and($otp->verify('jane@example.com', 'delivery', $delivery))->toBe(OtpService::VERIFIED)
        ->and($otp->verify('jane@example.com', 'password-reset', $reset))->toBe(OtpService::VERIFIED);
});

it('matches the address however the customer typed it', function () {
    $code = issueOtp('Jane@Example.com');

    // User::email() lowercases every write, so the keys are lowercased too —
    // otherwise a code mailed for one spelling could never be redeemed against
    // the other.
    expect(app(OtpService::class)->verify('JANE@example.com', 'test-purpose', $code))->toBe(OtpService::VERIFIED);
});

it('reports whether a code is still on file, so a screen can survive a refresh', function () {
    $otp = app(OtpService::class);

    expect($otp->isActive('jane@example.com', 'test-purpose'))->toBeFalse();

    $code = issueOtp('jane@example.com');

    expect($otp->isActive('jane@example.com', 'test-purpose'))->toBeTrue();

    $otp->verify('jane@example.com', 'test-purpose', $code);

    expect($otp->isActive('jane@example.com', 'test-purpose'))->toBeFalse();
});

it('drops a code and its cooldown on request', function () {
    $code = issueOtp('jane@example.com');

    app(OtpService::class)->forget('jane@example.com', 'test-purpose');

    // A completed reset shouldn't leave the next request waiting out a cooldown
    // for a code that no longer exists.
    expect(app(OtpService::class)->verify('jane@example.com', 'test-purpose', $code))->toBe(OtpService::EXPIRED)
        ->and(app(OtpService::class)->issue('jane@example.com', 'test-purpose', fn () => null))->toBe(OtpService::SENT);
});

it('does not let a code be redeemed against a different address', function () {
    $code = issueOtp('jane@example.com');

    expect(app(OtpService::class)->verify('attacker@example.com', 'test-purpose', $code))->toBe(OtpService::EXPIRED)
        ->and(app(OtpService::class)->verify('jane@example.com', 'test-purpose', $code))->toBe(OtpService::VERIFIED);
});
