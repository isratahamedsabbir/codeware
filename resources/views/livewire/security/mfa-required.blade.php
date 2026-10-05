{{--
    The forced-enrolment screen — where App\Http\Middleware\EnsureMfaEnforced
    sends an account whose audience has MFA switched on and which has no factor
    yet.

    Deliberately almost nothing but the shared panel: a user arriving here
    cannot do anything else in the portal until a factor exists, so the screen's
    whole job is to explain that and host the enrolment UI. Mounted once per
    portal (admin.mfa.required / vendor.mfa.required / delivery.mfa.required) so
    the panel, the passkey routes it drives and the session stay on the host
    whose policy is being enforced.

    No <x-layouts::auth> here — MfaRequired::render() supplies it with
    ->layout(), the same way every other Livewire auth screen in this app does,
    and the 'passkeys' flag rides along in that layout's data (see
    layouts/auth/split.blade.php for why the flag exists).
--}}
<div class="flex w-full flex-col gap-6">
    <x-auth-header :title="__('Two-factor authentication')"
        :description="__('One more step before you can use this portal.')" />

    {{-- The panel, exactly as it appears under Admin → My Profile.

         A reload on success, and only here: App\Support\Mfa reads the
         database, so the middleware is still holding this user until the next
         request, and nothing short of a reload would take them through to the
         page they were originally trying to reach. Under My Profile the panel
         just updates in place, so it does not listen for this. --}}
    <div x-on:mfa-updated.window="window.location.reload()">
        <livewire:security.mfa-panel :required="true" />
    </div>

    <div class="text-center">
        <p class="text-xs text-zinc-400">
            @if ($requiredForAdmin)
                {{ __('Need help? Another administrator can turn this requirement off for your role in Roles.') }}
            @else
                {{ __('Need help? Your administrator can turn this requirement off for your role in Roles.') }}
            @endif
        </p>
    </div>
</div>