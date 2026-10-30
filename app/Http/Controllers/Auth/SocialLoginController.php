<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Mfa;
use App\Support\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * "Continue with Google / Facebook" on the storefront's /login and /register.
 * Session-based (the API twin is Api\V1\Auth\SocialAuthController, which mints
 * tokens instead). The callback URL is this route, not the API one from .env, so
 * it has to be added to the provider's list of authorised redirect URIs.
 */
class SocialLoginController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(SocialAccount::configured($provider), 404);

        return Socialite::driver($provider)
            ->redirectUrl(route('social.callback', $provider))
            ->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse|Response
    {
        abort_unless(SocialAccount::configured($provider), 404);

        try {
            $socialUser = Socialite::driver($provider)
                ->redirectUrl(route('social.callback', $provider))
                ->user();
        } catch (Throwable) {
            return $this->fail(__('Sign in with :provider failed. Please try again.', ['provider' => ucfirst($provider)]));
        }

        if (! $socialUser->getEmail()) {
            return $this->fail(__('Your :provider account did not share an email address.', ['provider' => ucfirst($provider)]));
        }

        $user = SocialAccount::findOrCreate($provider, $socialUser);

        if ($user->is_blocked || $user->hasInactiveRole()) {
            return $this->fail(__('Your account access has been disabled.'));
        }

        // The provider proves who they are, not that they hold the second factor.
        if (Mfa::mustChallenge($user)) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);

            return redirect()->to(Mfa::challengeUrl());
        }

        Auth::login($user);
        $request->session()->regenerate();

        return app(LoginResponse::class)->toResponse($request);
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
