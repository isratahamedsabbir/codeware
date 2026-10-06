<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * Every question this app asks about multi-factor authentication, asked in one
 * place.
 *
 * Second factors come in two shapes and both count as "this account has a
 * second factor":
 *
 *   - a TOTP secret in an authenticator app (Fortify's two_factor_* columns), and
 *   - one or more WebAuthn passkeys (the `passkeys` table).
 *
 * They are interchangeable at the challenge — a user with only a passkey is
 * challenged exactly like one with only TOTP, and a user who lost their phone
 * can answer with the passkey instead — so no caller should be asking "do they
 * have *the* second factor", only hasFactorFor(). The only place the two are
 * asked apart is the panel that offers to set one up.
 *
 * The policy side (isRequiredFor) is deliberately separate from the enrolment
 * side (hasFactorFor): a password that checks out but an account with no
 * enrolled factor is the case the whole thing exists for. The policy is a
 * switch on the role, so it is set once per group of people rather than once
 * per login surface — see isRequiredFor().
 */
final class Mfa
{
    /** Admin panel + staff (see the 'access-admin' gate). */
    public const AUDIENCE_ADMIN = 'admin';

    /** Vendor portal (see the 'access-vendor-portal' gate). */
    public const AUDIENCE_VENDOR = 'vendor';

    /**
     * Delivery-rider portal (see the 'access-delivery-portal' gate).
     */
    public const AUDIENCE_DELIVERY = 'delivery';

    /**
     * Every audience an MFA policy can be enforced on, with the gate that
     * decides membership. Order matters: it is the order audiencesFor()
     * resolves a multi-role user in, so the most privileged door is named
     * first and the login screens read sensibly off it.
     *
     * Not the same list as who is *required* to use a second factor — that is
     * now per role (isRequiredFor()). What is left here is what the account can
     * see: which portals it belongs to, which is only used to say where to send
     * someone once they have answered.
     *
     * @var array<string, string>
     */
    public const AUDIENCES = [
        self::AUDIENCE_ADMIN => 'access-admin',
        self::AUDIENCE_VENDOR => 'access-vendor-portal',
        self::AUDIENCE_DELIVERY => 'access-delivery-portal',
    ];

    /**
     * Whether TOTP is available at all — Fortify's feature flag, read rather
     * than assumed, because a deployment can turn twoFactorAuthentication() off
     * and every "Enable" button in the panel then has nothing to enable.
     */
    public static function totpSupported(): bool
    {
        return Features::enabled(Features::twoFactorAuthentication());
    }

    /**
     * Whether passkeys are available at all (see Features::passkeys()).
     */
    public static function passkeysSupported(): bool
    {
        return Features::enabled(Features::passkeys());
    }

    /**
     * True when the account has a confirmed TOTP secret.
     *
     * An unconfirmed secret (the QR was shown and the user walked away) is not
     * a factor: it is a pending setup. Fortify's own InteractsWithTwoFactorState
     * counts a secret as enabled regardless of confirmation, which is right for
     * "disable", and wrong for this — see TwoFactorAuthenticatable's
     * hasEnabledTwoFactorAuthentication() for the confirmed variant.
     */
    public static function totpEnabledFor(User $user): bool
    {
        if (! self::totpSupported() || ! filled($user->two_factor_secret)) {
            return false;
        }

        if (Fortify::confirmsTwoFactorAuthentication()) {
            return ! is_null($user->two_factor_confirmed_at);
        }

        return true;
    }

    /**
     * True when the account has at least one registered passkey.
     */
    public static function hasPasskeyFor(User $user): bool
    {
        if (! self::passkeysSupported()) {
            return false;
        }

        // The trait isn't on the model at all when passkeys are switched off,
        // which is the case this guards: calling a relation that doesn't exist
        // is a fatal, not a false.
        if (! in_array(PasskeyAuthenticatable::class, class_uses_recursive($user), true)) {
            return false;
        }

        return $user->hasPasskeysEnabled();
    }

    /**
     * True when this account can answer a second-factor challenge at all —
     * either kind of factor.
     */
    public static function hasFactorFor(User $user): bool
    {
        return self::totpEnabledFor($user) || self::hasPasskeyFor($user);
    }

    /**
     * The audiences this account belongs to, in AUDIENCES order.
     *
     * The gates are asked rather than the roles re-listed here, so a new role
     * that satisfies 'access-vendor-portal' is covered by policy the moment the
     * gate is — this class cannot drift out of step with AppServiceProvider.
     *
     * @return list<string>
     */
    public static function audiencesFor(User $user): array
    {
        $audiences = [];

        foreach (self::AUDIENCES as $audience => $ability) {
            if (Gate::forUser($user)->allows($ability)) {
                $audiences[] = $audience;
            }
        }

        return $audiences;
    }

    /**
     * Whether this account is required to have a second factor.
     *
     * The switch lives on the role (Roles → MFA), so this asks whether any role
     * the account holds has it on. That is deliberately one question about the
     * account rather than one per portal: a person who is both a vendor and a
     * delivery rider passes through the vendor door, and a role flag is carried
     * with them wherever they sign in — the same reach the audience policies
     * used to have by way of walking every audience the account is in, without
     * this class having to know what "the API audience" is.
     */
    public static function isRequiredFor(User $user): bool
    {
        return self::adminMustHaveFactor($user)
            || $user->roles()->where('mfa_enabled', true)->exists();
    }

    /**
     * Whether this account may get into the portal it's standing at right now
     * with nothing but its password.
     *
     * The audit that decides this. True means one of two things, and the caller
     * has to treat them differently, so they are named apart by
     * needsEnrolmentFor() below: either the account has a factor and is simply
     * not past the challenge yet (login-time), or it has none at all and has to
     * be walked through enrolment first.
     */
    public static function mustChallenge(User $user): bool
    {
        if (! self::isRequiredFor($user)) {
            return false;
        }

        return self::hasFactorFor($user);
    }

    /**
     * Whether this account has no second factor at all and the policy says it
     * needs one — the case App\Http\Middleware\EnsureMfaEnforced sends to the
     * enrolment screen.
     */
    public static function needsEnrolmentFor(User $user): bool
    {
        // Never, by design: a role with 2FA switched on makes the second factor
        // available (the Profile section appears) and challenges whoever has set
        // one up, but it does not trap an account that has not. Nobody is held
        // on the enrolment screen the moment an admin flips the switch.
        // The one exception: the admin role, once security.require_admin_mfa is on
        // (the production default). An admin account is a path to code execution
        // (plugin/theme install, File Manager), so it cannot sit at a password.
        return self::adminMustHaveFactor($user) && ! self::hasFactorFor($user);
    }

    /** Whether the admin-role MFA requirement applies to this account. */
    public static function adminMustHaveFactor(User $user): bool
    {
        return (bool) config('security.require_admin_mfa') && $user->hasRole('admin');
    }

    /**
     * The URL of the second-factor challenge, on whichever host is being asked.
     *
     * Fortify registers /two-factor-challenge with no domain, so it answers on
     * every host — which is what makes the per-portal logins able to point at
     * it: each panel's session cookie is host-only, so the challenge has to be
     * completed on the same host the login was started on, and building the URL
     * from the panel's own configured host is what guarantees that. The Livewire
     * logins can't use route() here because it would resolve against APP_URL,
     * not the panel.
     *
     * The host comes from the audience's own configuration rather than from the
     * current request, and that is not a stylistic choice. Both are the same host
     * at runtime, but only one of them is the same host *everywhere*: a Livewire
     * component test has no panel host to ask (its request is the Livewire update
     * endpoint on APP_URL), and url() would silently hand back the main site —
     * which is the exact bug this method's callers had before it existed, since
     * the admin login used to build the URL from its own config for this reason.
     */
    public static function challengeUrl(?string $audience = null): string
    {
        $base = match ($audience) {
            self::AUDIENCE_ADMIN => rtrim((string) config('app.admin_url'), '/'),
            self::AUDIENCE_VENDOR => self::baseUrlForHost(config('app.vendor_host')),
            self::AUDIENCE_DELIVERY => self::baseUrlForHost(config('app.delivery_host')),
            default => null,
        };

        return filled($base)
            ? $base.'/two-factor-challenge'
            : url('/two-factor-challenge');
    }

    /**
     * scheme://host for a portal configured as a bare hostname.
     *
     * The vendor and delivery portals are configured as hosts rather than as full
     * URLs — config('app.vendor_host') and config('app.delivery_host') are derived
     * by parsing the host out of VENDOR_URL / DELIVERY_URL, because that is the
     * form the rest of the app matches requests on. The scheme is not part of
     * that, so it is taken from APP_URL the same way config/passkeys.php does
     * when it turns the same two hosts into WebAuthn origins.
     */
    private static function baseUrlForHost(mixed $host): ?string
    {
        if (blank($host)) {
            return null;
        }

        if (str_contains($host, '://')) {
            return rtrim($host, '/');
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return $scheme.'://'.$host;
    }

    /**
     * Where a user is sent once their second factor is proven, by audience.
     *
     * Only consulted on the API's stateless challenge, where there is no session
     * to regenerate and therefore no intended-URL to honour.
     *
     * @return array<string, string>
     */
    public static function landingPathFor(?string $audience): string
    {
        return match ($audience) {
            self::AUDIENCE_ADMIN => '/admin',
            self::AUDIENCE_VENDOR => '/vendor',
            self::AUDIENCE_DELIVERY => '/delivery',
            default => config('fortify.home', '/dashboard'),
        };
    }

    /**
     * Whether the half-finished login sitting in this session is one that can
     * also be answered with a passkey.
     *
     * The challenge page is Fortify's own Blade view
     * (pages::auth.two-factor-challenge), which has no idea an account might have
     * a passkey, and this is the question it has to ask to know whether to offer
     * the option at all. It lives here rather than in the view because it is the
     * same "does this account have a factor of kind X" question as everywhere
     * else, and because the alternative — a query written inline in markup —
     * would be a second place the shape of the answer is decided.
     *
     * False whenever there is no challenged login, which is the state the page
     * renders in after Fortify's own checks have passed it.
     */
    public static function challengedUserHasPasskey(): bool
    {
        $id = session()->get('login.id');

        if (! $id) {
            return false;
        }

        $user = User::find($id);

        return $user instanceof User && self::hasPasskeyFor($user);
    }

    /**
     * Whether the account mid-challenge has a confirmed authenticator-app secret.
     *
     * The other half of challengedUserHasPasskey(): the challenge page offers
     * only the factors the account actually holds, so a passkey-only account is
     * not shown a code field it has nothing to type into.
     */
    public static function challengedUserHasTotp(): bool
    {
        $id = session()->get('login.id');

        if (! $id) {
            return false;
        }

        $user = User::find($id);

        return $user instanceof User && self::totpEnabledFor($user);
    }
}
