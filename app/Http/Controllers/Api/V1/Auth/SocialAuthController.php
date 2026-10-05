<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiMfa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Google/Facebook login for customer accounts (same `users` table as
 * Login/RegisterController — no admin role here). Stateless because the
 * Next.js frontend and this API sit on separate origins, so there's no shared
 * session to round-trip the OAuth state through; the provider's own `state`
 * param (which Socialite's stateless mode still generates and verifies via a
 * short-lived cookie) is enough CSRF protection for the redirect step.
 *
 * Flow: frontend hits GET /redirect, gets the provider URL, sends the browser
 * there directly (not a fetch — it has to be a real navigation for the
 * provider's own login UI to show). The provider redirects back to /callback
 * on this API, which finishes the exchange and 302s the browser to
 * `{FRONTEND_URL}/auth/callback` with the Sanctum token in the query string
 * for the frontend to pick up and store.
 *
 * Or, when the account has a second factor and the API policy is on, with a
 * challenge instead of a token (see App\Support\ApiMfa) — the frontend shows the
 * second-factor screen and posts the answer to /auth/mfa/verify. The provider
 * only ever establishes who the person is; it has no way to establish the second
 * factor, so a social sign-in cannot be treated as having done so.
 */
class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider): JsonResponse
    {
        $this->ensureSupported($provider);

        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response()->json(['data' => ['url' => $url]]);
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->ensureSupported($provider);

        $frontendCallback = rtrim(config('app.frontend_url'), '/').'/auth/callback';

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (Throwable) {
            return redirect()->away("{$frontendCallback}?error=oauth_failed");
        }

        if (! $socialUser->getEmail()) {
            return redirect()->away("{$frontendCallback}?error=no_email");
        }

        $user = $this->findOrCreateUser($provider, $socialUser);

        if ($user->is_blocked || $user->hasInactiveRole()) {
            return redirect()->away("{$frontendCallback}?error=account_disabled");
        }

        // A Google/Facebook sign-in has already proved possession of the provider
        // account, but it has proved nothing about the second factor this account
        // may also have enrolled — and unlike the password flow, the identity
        // provider is not going to ask about it. Same policy as
        // Api\V1\Auth\LoginController: with the API policy on and a factor
        // enrolled, no token is minted.
        //
        // Handed to the frontend the same way the token itself is: in the query
        // string of the callback redirect, which is the only channel this
        // stateless OAuth hand-off has. The value is an encrypted blob whose
        // plaintext would be harmless to a third party, and it is short-lived.
        if (ApiMfa::isRequiredFor($user)) {
            $token = rawurlencode(ApiMfa::issue($user));
            $methods = implode(',', ApiMfa::methodsFor($user));

            return redirect()->away(
                "{$frontendCallback}?error=mfa_required&mfa_token={$token}&methods={$methods}"
            );
        }

        $token = $user->createToken('customer-api')->plainTextToken;

        return redirect()->away("{$frontendCallback}?token={$token}");
    }

    private function findOrCreateUser(string $provider, SocialiteUser $socialUser): User
    {
        $user = User::where('provider', $provider)->where('provider_id', $socialUser->getId())->first();

        if ($user) {
            return $user;
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            $user->forceFill([
                'provider' => $user->provider ?? $provider,
                'provider_id' => $user->provider_id ?? $socialUser->getId(),
            ])->save();

            return $user;
        }

        return User::create([
            'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User',
            'email' => $socialUser->getEmail(),
            'password' => Str::random(40),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'email_verified_at' => now(),
        ]);
    }

    private function ensureSupported(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
    }
}
