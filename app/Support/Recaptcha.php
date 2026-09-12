<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Whether the login page's reCAPTCHA widget/verification is actually active —
 * true only when the admin has both switched it on and configured the
 * relevant key, both on the reCAPTCHA card under Settings → Env.
 */
class Recaptcha
{
    public static function enabled(): bool
    {
        return (bool) Setting::get('recaptcha_enabled', false) && filled(config('services.recaptcha.site_key'));
    }

    public static function verificationRequired(): bool
    {
        return (bool) Setting::get('recaptcha_enabled', false) && filled(config('services.recaptcha.secret_key'));
    }
}
