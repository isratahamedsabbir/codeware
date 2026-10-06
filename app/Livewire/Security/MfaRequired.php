<?php

namespace App\Livewire\Security;

use App\Support\Mfa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The screen a user is held at when a policy is switched on for their audience
 * and they have no second factor yet (App\Http\Middleware\EnsureMfaEnforced).
 *
 * Nothing here but the shared App\Livewire\Security\MfaPanel and a way out:
 * the reason the panel is a component of its own is precisely so this screen and
 * Admin → My Profile cannot drift apart. Same panel, same actions, one
 * implementation.
 *
 * Mounted once per portal, on that portal's own host, under a route name of the
 * portal's own (admin.mfa.required, vendor.mfa.required, delivery.mfa.required)
 * — the session and the passkey routes the panel drives are host-only, so the
 * screen has to live on the host whose policy is being enforced.
 */
class MfaRequired extends Component
{
    /**
     * The panel announces every change to a factor. Re-rendering is all this
     * needs to do: it is what makes the "Continue" button appear the moment a
     * factor exists. It must not reload the page — a reload would throw away the
     * recovery codes the panel has just put on screen, which are shown only once.
     */
    #[On('mfa-updated')]
    public function refreshed(): void {}

    public function render()
    {
        $user = Auth::user();

        // Where "Continue" goes: the dashboard of whichever portal this host is.
        [$dashboard, $logout] = match (request()->getHost()) {
            config('app.vendor_host') => ['vendor.dashboard', 'vendor.logout'],
            config('app.delivery_host') => ['delivery.dashboard', 'delivery.logout'],
            default => ['admin.dashboard', 'admin.logout'],
        };

        return view('livewire.security.mfa-required', [
            'requiredFor' => $user ? Mfa::audiencesFor($user) : [],
            'hasFactor' => $user && Mfa::hasFactorFor($user),
            'continueUrl' => route($dashboard),
            'logoutRoute' => route($logout),
            // Why the panel below is on screen at all, so the help line can say
            // "another administrator" to an admin and "your administrator" to a
            // vendor — there is nothing to point at on a portal where the person
            // being nagged is the one who could turn it off.
            'requiredForAdmin' => $user && in_array(Mfa::AUDIENCE_ADMIN, Mfa::audiencesFor($user), true),
        ])->layout('layouts::auth', [
            'title' => 'Set up two-factor authentication',

            // The passkey registration block is one of the two ways to satisfy
            // this screen, and it is a WebAuthn ceremony that needs
            // window.Passkeys. This layout serves the storefront bundle, which
            // does not carry it, so the client is opted into by flag rather than
            // assumed. See layouts/auth/split.blade.php.
            'passkeys' => true,

            // The two method cards need more room than a login form does.
            'wide' => true,
        ]);
    }
}
