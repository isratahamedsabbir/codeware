<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Whether the login page's reCAPTCHA widget/verification is actually active.
 *
 * The switch is on the role (Roles → reCAPTCHA), not in settings, so the answer
 * to "is reCAPTCHA on?" is a question about the account trying to sign in. The
 * keys are still settings data — they are the site/secret pair under
 * Settings → Env — and a requirement without them cannot be met, so both halves
 * of the old condition are kept: a role asking for reCAPTCHA with no keys
 * configured would otherwise render a widget that can never pass.
 */
class Recaptcha
{
    /**
     * Whether the login page is worth rendering the widget on at all.
     *
     * This is the page-load question, and the account is not known yet — the
     * field asking who is signing in is the one below it. So it answers "does
     * any role want reCAPTCHA", and the widget is shown to everyone who reaches
     * that form. requiredFor() is what decides whether a given sign-in has to
     * pass it.
     *
     * Both keys are required, not just the site key. The site key alone is enough
     * to render a widget that looks right and can never be verified against — and a
     * login form asking for a token nothing can check is a door with no handle on
     * the inside, not a security control.
     */
    public static function enabled(): bool
    {
        return self::anyRoleWantsIt()
            && filled(config('services.recaptcha.site_key'))
            && filled(config('services.recaptcha.secret_key'));
    }

    /**
     * Whether this particular account has to answer the challenge.
     *
     * Null is a deliberate answer, not a shrug. The captcha is asked ahead of the
     * password check, so that an address with no account behind it and a wrong
     * password produce the same "credentials are wrong" — a stranger gets no
     * captcha to solve, and so learns nothing about which addresses exist or
     * whether they are behind a role that wants one.
     */
    public static function requiredFor(?User $user): bool
    {
        if (! $user || ! self::anyRoleWantsIt()) {
            return false;
        }

        return $user->roles()->where('recaptcha_enabled', true)->exists()
            && filled(config('services.recaptcha.secret_key'));
    }

    /**
     * Whether any role at all has the switch on.
     *
     * Asked with a query rather than a cached constant so a role switched on a
     * second ago takes effect on the next sign-in without anything having to
     * remember to clear a cache — the cost is one indexed exists() per login
     * page render.
     */
    private static function anyRoleWantsIt(): bool
    {
        return Role::where('recaptcha_enabled', true)->exists();
    }
}
