<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');

        if (! $secret) {
            return;
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // Same stance as the reCAPTCHA rule: no verdict is not a "no", so an
            // unreachable Cloudflare must not lock everyone out of login.
            Log::warning('Turnstile verification request failed: '.$e->getMessage());

            return;
        }

        if ($response->json('success') === true) {
            return;
        }

        $fail('Please confirm you are not a robot.');
    }
}
