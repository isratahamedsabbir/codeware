<?php

namespace App\Http\Middleware;

use App\Support\Recaptcha;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the active captcha (reCAPTCHA or Turnstile, whichever Settings → Env
 * selects) mandatory on public POST endpoints once its keys are saved. The
 * token may arrive as captcha_token, g-recaptcha-response or
 * cf-turnstile-response. With no keys configured it is a no-op.
 */
class VerifyCaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Recaptcha::configured()) {
            return $next($request);
        }

        $token = $request->input('captcha_token')
            ?? $request->input('g-recaptcha-response')
            ?? $request->input('cf-turnstile-response');

        $validator = Validator::make(
            ['captcha_token' => $token],
            ['captcha_token' => ['required', 'string', Recaptcha::rule()]],
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Captcha verification failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return $next($request);
    }
}
