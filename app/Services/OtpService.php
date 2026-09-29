<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * One-time codes emailed to an address and redeemed once, scoped by purpose so
 * two unrelated flows can never spend each other's codes.
 *
 * This is the hardened of the project's two hand-rolled code flows, and the one
 * the softer of them is a candidate to be rewritten onto: the delivery code
 * (App\Livewire\Delivery\Orders\Show) stores a hash, counts wrong guesses and
 * burns the code after a handful, while the chat widget's code sits in the cache
 * in plaintext with no attempt limit at all. Everything here follows the
 * delivery flow's behaviour, and the account password reset (see
 * PasswordResetService) is the first caller to use it as a service.
 *
 * The three properties that matter:
 *
 *  - The code is stored hashed, so a leaked cache row — a shared Redis, a
 *    cache dump — is not a set of working passwords.
 *  - MAX_ATTEMPTS wrong guesses burn the code. A six-digit code is a million
 *    candidates, and without a cap a caller could simply keep guessing until
 *    one lands; burning forces a new code, and a new code costs the attacker a
 *    fresh email that has to be read from the inbox anyway.
 *  - A code is only cached after its mail has actually been sent, so a failed
 *    send leaves nothing behind to be redeemed, and nothing to be confused with
 *    a code the customer simply hasn't received yet.
 *
 * And the one property about the asking rather than the code: MAX_ISSUES_PER_HOUR.
 * A short resend cooldown only stops someone hammering one button — a minute
 * apart is a hundred and forty-four hundred emails a day, which is a denial of
 * service aimed at the customer's inbox rather than at the shop. The quota is
 * counted against the address, not the caller, so it holds however many
 * different addresses and IP addresses the requests come from.
 */
class OtpService
{
    /** How long a code stays redeemable. */
    public const TTL_MINUTES = 10;

    /** How long an address has to wait before another code is issued. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /** Wrong guesses allowed before the code is burned. */
    public const MAX_ATTEMPTS = 5;

    /**
     * How many codes one address may be sent within QUOTA_HOURS.
     *
     * Generous enough that a customer who fumbled an address twice still has
     * room to get in, and small enough that nobody's inbox can be used as a
     * target. Note this counts codes actually sent, not requests: the cooldown
     * above is checked first, so leaning on the resend button does not spend
     * any of it.
     */
    public const MAX_ISSUES_PER_HOUR = 5;

    /** The window MAX_ISSUES_PER_HOUR is counted over. */
    public const QUOTA_HOURS = 1;

    // issue() outcomes.
    public const SENT = 'sent';

    public const THROTTLED = 'throttled';

    public const RATE_LIMITED = 'rate_limited';

    public const FAILED = 'failed';

    // verify() outcomes.
    public const VERIFIED = 'verified';

    public const EXPIRED = 'expired';

    public const INVALID = 'invalid';

    public const LOCKED = 'locked';

    /**
     * Generates a code, mails it through $send, and caches it only if that mail
     * went out.
     *
     * $send is handed the code and is responsible for delivering it — the caller
     * owns the mailable, since what a code is for is entirely its own business.
     * A throw from it is swallowed and reported as FAILED, because a code
     * nobody received must not be treated as a live one.
     *
     * @param  callable(string): void  $send
     */
    public function issue(string $email, string $purpose, callable $send): string
    {
        $email = $this->normalize($email);

        if (Cache::has($this->cooldownKey($email, $purpose))) {
            return self::THROTTLED;
        }

        if (! $this->claimQuota($email, $purpose)) {
            return self::RATE_LIMITED;
        }

        $code = (string) random_int(100000, 999999);

        try {
            $send($code);
        } catch (Throwable) {
            return self::FAILED;
        }

        Cache::put($this->codeKey($email, $purpose), Hash::make($code), now()->addMinutes(self::TTL_MINUTES));
        Cache::put($this->cooldownKey($email, $purpose), true, now()->addSeconds(self::RESEND_COOLDOWN_SECONDS));
        Cache::forget($this->attemptsKey($email, $purpose));

        return self::SENT;
    }

    /**
     * Counts one code against an address's hourly quota and says whether it was
     * allowed, incrementing either way.
     *
     * Public because the caller has to spend it on the requests that send
     * nothing as well — see PasswordResetService::requestCode, where an address
     * with no account must spend the same quota a real send would, or the
     * difference between the two cases would answer the question this whole
     * service is built to keep unanswerable: who has an account here.
     *
     * @return bool whether the request is still inside MAX_ISSUES_PER_HOUR
     */
    public function claimQuota(string $email, string $purpose): bool
    {
        $key = $this->quotaKey($email, $purpose);

        // add() seeds the counter at zero only if this is the first request in
        // the window; increment() then counts this one. Doing it in this order
        // keeps a burst from slipping between the two.
        Cache::add($key, 0, now()->addHours(self::QUOTA_HOURS));

        return Cache::increment($key) <= self::MAX_ISSUES_PER_HOUR;
    }

    /**
     * Checks a code against the one on file, spending it on a match.
     *
     * Returns one of the verify() outcome constants. INVALID means it was wrong
     * but guesses remain, LOCKED means the code has just been burned and a new
     * one has to be requested, and EXPIRED means there was never a code, or the
     * one there was timed out.
     */
    public function verify(string $email, string $purpose, string $code): string
    {
        $email = $this->normalize($email);
        $code = trim($code);

        $hash = Cache::get($this->codeKey($email, $purpose));

        if (! $hash) {
            return self::EXPIRED;
        }

        if (! Hash::check($code, $hash)) {
            Cache::add($this->attemptsKey($email, $purpose), 0, now()->addMinutes(self::TTL_MINUTES));
            $attempts = Cache::increment($this->attemptsKey($email, $purpose));

            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->forget($email, $purpose);

                return self::LOCKED;
            }

            return self::INVALID;
        }

        $this->forget($email, $purpose);

        return self::VERIFIED;
    }

    /**
     * Drops the code, its attempt counter and the resend cooldown, so a fresh
     * code can be issued right away. Used when a verified code is spent on a
     * completed reset, and on the way out of a locked-out address.
     */
    public function forget(string $email, string $purpose): void
    {
        $email = $this->normalize($email);

        Cache::forget($this->codeKey($email, $purpose));
        Cache::forget($this->attemptsKey($email, $purpose));
        Cache::forget($this->cooldownKey($email, $purpose));
    }

    /**
     * Whether a code is currently on file — how a screen that offers "enter the
     * code we sent" knows it is still worth showing, across a refresh.
     */
    public function isActive(string $email, string $purpose): bool
    {
        return Cache::has($this->codeKey($this->normalize($email), $purpose));
    }

    public function cooldownKey(string $email, string $purpose): string
    {
        return "otp:{$purpose}:{$this->normalize($email)}:cooldown";
    }

    private function codeKey(string $email, string $purpose): string
    {
        return "otp:{$purpose}:{$this->normalize($email)}:code";
    }

    private function attemptsKey(string $email, string $purpose): string
    {
        return "otp:{$purpose}:{$this->normalize($email)}:attempts";
    }

    private function quotaKey(string $email, string $purpose): string
    {
        return "otp:{$purpose}:{$this->normalize($email)}:quota";
    }

    /**
     * The cache keys are matched on the address exactly as it is stored, and
     * User::email() lowercases every write — so the lookup is lowered too. Without
     * this, "Jane@Example.com" would be issued a code under its own key while the
     * verification step looked under the lowercased one, and no code would ever
     * be accepted.
     */
    private function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }
}
