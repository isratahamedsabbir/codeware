<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret_key');

        if (! $secret) {
            return;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            // v3 has no checkbox — Google instead scores the request 0.0 (bot) to
            // 1.0 (human). 0.5 is Google's own recommended cutoff for "likely human".
            if ($response->json('success') === true && $response->json('score', 0) >= 0.5) {
                return;
            }
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification request failed: '.$e->getMessage());
        }

        $fail('Please confirm you are not a robot.');
    }
}
