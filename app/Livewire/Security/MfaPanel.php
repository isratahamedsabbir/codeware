<?php

namespace App\Livewire\Security;

use App\Support\Mfa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Support\WebAuthn;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * The one place a signed-in user manages their own second factors, and the only
 * place that writes two_factor_* / passkeys on their behalf.
 *
 * Mounted from two directions:
 *
 *   - Admin → My Profile, alongside the password card, for users who chose to
 *     turn MFA on. Also linked from /settings/security on the main host, which
 *     is where a customer account manages its own.
 *   - The per-portal enrolment screen (App\Livewire\Security\MfaRequired), which
 *     App\Http\Middleware\EnsureMfaEnforced bounces users to once a policy is
 *     switched on for their audience. Same component, so "set one up" looks the
 *     same whether it was their idea or the policy's.
 *
 * Every action here calls Fortify's / passkeys' own action classes directly
 * rather than posting to their HTTP routes, exactly as the settings page's
 * two-factor section does — Livewire has no CSRF-protected form post in play,
 * and the routes Fortify registers for the same operations sit unused. The
 * routes that *are* used are the passkey ones, because a WebAuthn ceremony
 * needs the options to survive a round trip through the browser and only the
 * package's session storage arranges that; see passkeyRegistrationOptions().
 */
class MfaPanel extends Component
{
    /** Whether TOTP can be offered at all (Fortify's feature flag). */
    #[Locked]
    public bool $totpSupported = false;

    /** Whether passkeys can be offered at all. */
    #[Locked]
    public bool $passkeysSupported = false;

    #[Locked]
    public bool $totpEnabled = false;

    #[Locked]
    public bool $passkeysEnabled = false;

    /** True when the surrounding screen is the forced-enrolment one. */
    #[Locked]
    public bool $required = false;

    /** Whether password confirmation is demanded before anything is changed. */
    #[Locked]
    public bool $confirmPassword = false;

    public string $current_password = '';

    public bool $confirmPrompt = false;

    /** The QR / setup key, shown between "Enable" and "Confirm". */
    public string $qrCodeSvg = '';

    public string $manualSetupKey = '';

    public bool $verifyingTotp = false;

    #[Validate('required|digits:6', onUpdate: false)]
    public string $code = '';

    public string $passkeyName = '';

    /**
     * Plaintext recovery codes, shown once and then dropped from the component.
     *
     * Never a Livewire property that survives a render for any longer than it
     * takes to be read: it goes into the browser, and leaving eight usable
     * credentials sitting in a signed payload is the one thing this whole screen
     * exists to prevent. hideRecoveryCodes() clears it, and so does leaving the
     * screen.
     *
     * @var list<string>
     */
    public array $recoveryCodes = [];

    /** JSON options handed to navigator.credentials.create(). */
    public string $passkeyOptions = '';

    public bool $addingPasskey = false;

    /** Recovery codes are on screen — the block that prints them is open. */
    public bool $showingRecoveryCodes = false;

    public function mount(bool $required = false, bool $confirmPassword = true): void
    {
        $this->required = $required;
        $this->confirmPassword = $confirmPassword;

        $this->refreshState();
    }

    /**
     * Re-reads both factor kinds off the user.
     *
     * Called after every action rather than derived, because the state that
     * matters is what is *persisted*: an abandoned setup leaves a
     * two_factor_secret behind that has never been confirmed, and reading the
     * columns straight would report MFA as on for an account that cannot answer
     * a challenge.
     */
    public function refreshState(): void
    {
        $user = Auth::user();

        $this->totpSupported = Mfa::totpSupported();
        $this->passkeysSupported = Mfa::passkeysSupported();
        $this->totpEnabled = $user ? Mfa::totpEnabledFor($user) : false;
        $this->passkeysEnabled = $user ? Mfa::hasPasskeyFor($user) : false;
    }

    /**
     * The enrolled passkeys, for the list.
     *
     * @return array<int, array{id: int, name: string, last_used_at: ?string}>
     */
    public function getPasskeysProperty(): array
    {
        if (! $this->passkeysSupported) {
            return [];
        }

        return Auth::user()?->passkeys()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($passkey): array => [
                'id' => $passkey->getKey(),
                'name' => $passkey->name,
                'last_used_at' => $passkey->last_used_at?->toDisplay(),
            ])
            ->all() ?? [];
    }

    /**
     * Whether the account is past setup as far as the policy is concerned —
     * what App\Http\Middleware\EnsureMfaEnforced checks on the next request.
     */
    public function getSatisfiedProperty(): bool
    {
        return ! $this->required || Auth::user() === null || Mfa::hasFactorFor(Auth::user());
    }

    public function toggleConfirmPrompt(): void
    {
        $this->confirmPrompt = ! $this->confirmPrompt;

        if (! $this->confirmPrompt) {
            $this->reset('current_password');
            $this->resetErrorBag('current_password');
        }
    }

    /**
     * Turn the password prompt off once the typed password is right.
     *
     * The confirmation is recorded in the session, not in a Livewire property:
     * a Livewire property would travel to the browser on every subsequent
     * render and come back on every subsequent request, which is exactly what a
     * credential must not do. This is the same shape as Laravel's own
     * password.confirm — a timestamp in the session, good for a couple of hours
     * (see EnsureMfaConfirmed) — except that it is scoped to changing a factor,
     * not to the whole panel.
     */
    public function confirmPasswordAction(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        Session::put('mfa.confirmed_at', time());

        $this->reset('current_password', 'confirmPrompt');
        $this->resetErrorBag();
    }

    /**
     * Ask for the current password before doing anything that changes or
     * removes a factor.
     *
     * @throws ValidationException
     */
    private function authorizeChange(): void
    {
        if (! $this->confirmPassword || Session::get('mfa.confirmed_at')) {
            return;
        }

        $this->confirmPrompt = true;
        $this->addError('current_password', 'Confirm your password to continue.');

        throw ValidationException::withMessages([
            'current_password' => 'Confirm your password to continue.',
        ]);
    }

    /**
     * Step 1 of TOTP enrolment: generate a secret and show it to scan.
     */
    public function startTotpSetup(EnableTwoFactorAuthentication $enable): void
    {
        $this->authorizeChange();

        if (! $this->totpSupported) {
            return;
        }

        $this->reset('code', 'verifyingTotp');
        $this->resetErrorBag();

        $enable(Auth::user());

        $user = Auth::user()->fresh();

        try {
            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Throwable) {
            // The secret is written but could not be rendered (an APP_KEY that
            // no longer decrypts it, a missing QR writer). Say so rather than
            // leaving a permanently half-enabled account with no way to finish.
            $this->addError('totp', 'Could not read the new setup key. Try again, or use a passkey instead.');

            return;
        }

        $this->verifyingTotp = true;

        $this->refreshState();
    }

    /**
     * Step 2: the code from the authenticator app proves the secret works, so
     * the factor counts as enrolled (Fortify's `confirm` option).
     *
     * @throws ValidationException
     */
    public function confirmTotp(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->authorizeChange();

        $this->validate();

        $user = Auth::user();

        try {
            $confirm($user, $this->code);
        } catch (Throwable) {
            // Fortify's ConfirmTwoFactorAuthentication throws a plain
            // ValidationException on a bad code; anything else reaching here is
            // a decrypt/verify failure, which is the same story to the user.
            throw ValidationException::withMessages([
                'code' => ['That code was not accepted. Check your authenticator app and try again.'],
            ]);
        }

        $this->reset('code', 'verifyingTotp', 'qrCodeSvg', 'manualSetupKey');
        $this->resetErrorBag();

        $this->refreshState();

        $this->dispatch('mfa-updated');
    }

    /**
     * Abandon a setup that was started and not finished.
     *
     * Without this the account keeps an unconfirmed secret, which
     * Mfa::totpEnabledFor() correctly ignores — so it is not a security hole,
     * but it is a secret the next "Enable" click would be invisible behind.
     */
    public function cancelTotpSetup(DisableTwoFactorAuthentication $disable): void
    {
        $this->authorizeChange();

        $disable(Auth::user());

        $this->reset('code', 'verifyingTotp', 'qrCodeSvg', 'manualSetupKey');
        $this->resetErrorBag();

        $this->refreshState();
    }

    /**
     * Turn TOTP off. Recovery codes go with it — Fortify's action re-issues none,
     * so leaving them behind would hand out codes that can no longer be spent.
     */
    public function disableTotp(DisableTwoFactorAuthentication $disable): void
    {
        $this->authorizeChange();

        $disable(Auth::user());

        $this->reset('code', 'verifyingTotp', 'qrCodeSvg', 'manualSetupKey');
        $this->resetErrorBag();

        $this->refreshState();

        $this->dispatch('mfa-updated');
    }

    /**
     * Show the codes already on the account, without reissuing them.
     *
     * Reading them at all requires the password — they are the fallback for a
     * lost phone, so a window into them is as sensitive as the TOTP secret
     * itself, which is why this sits behind authorizeChange() like the rest.
     */
    public function showRecoveryCodes(): void
    {
        $this->authorizeChange();

        if (! $this->totpEnabled) {
            return;
        }

        $this->recoveryCodes = $this->readRecoveryCodes();
        $this->showingRecoveryCodes = true;
    }

    /**
     * Replace all eight codes.
     *
     * The action returns nothing (it only writes the column), so the fresh codes
     * are read back off the user afterwards rather than being assumed — that read
     * is also what proves the write worked.
     */
    public function generateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $this->authorizeChange();

        if (! $this->totpEnabled) {
            return;
        }

        $generate(Auth::user());

        $this->recoveryCodes = $this->readRecoveryCodes();
        $this->showingRecoveryCodes = true;
    }

    /**
     * Done reading — drop the plaintext out of the component.
     */
    public function hideRecoveryCodes(): void
    {
        $this->reset('recoveryCodes', 'showingRecoveryCodes');
    }

    /**
     * The stored codes, decrypted, as a flat list of strings.
     *
     * @return list<string>
     */
    private function readRecoveryCodes(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        try {
            return array_values((array) $user->fresh()->recoveryCodes());
        } catch (Throwable) {
            // An APP_KEY that no longer decrypts the column. Say so instead of
            // rendering an empty list, which reads as "you have no codes".
            $this->addError('recoveryCodes', 'The saved recovery codes could not be read.');

            return [];
        }
    }

    /**
     * Step 1 of passkey enrolment: hand the browser its
     * PublicKeyCredentialCreationOptions.
     *
     * The options are stashed in the session under the key laravel/passkeys
     * reads back, because a WebAuthn ceremony needs the challenge the server
     * generated to still be the one it verifies against, and that has to survive
     * the round trip out to the authenticator and back. Its StorePasskey pulls
     * exactly this key.
     */
    public function passkeyRegistrationOptions(): void
    {
        $this->authorizeChange();

        if (! $this->passkeysSupported) {
            return;
        }

        $this->validate([
            'passkeyName' => ['required', 'string', 'max:255'],
        ]);

        $options = app(GenerateRegistrationOptions::class)(Auth::user());

        session()->put('passkey.registration_options', WebAuthn::toJson($options));

        $this->passkeyOptions = WebAuthn::toJson($options);
        $this->addingPasskey = true;
    }

    /**
     * Step 2: the credential the authenticator produced, verified and stored.
     *
     * @param  array<string, mixed>  $credential
     */
    public function addPasskey(array $credential, StorePasskey $store): void
    {
        $this->authorizeChange();

        if (! $this->passkeysSupported) {
            return;
        }

        $this->validate([
            'passkeyName' => ['required', 'string', 'max:255'],
        ]);

        $serialized = session()->pull('passkey.registration_options');

        if (! $serialized) {
            throw ValidationException::withMessages([
                'passkeyName' => ['The passkey request expired. Start again.'],
            ]);
        }

        try {
            $publicKeyCredential = WebAuthn::fromJson(
                json_encode($credential, JSON_THROW_ON_ERROR) ?: '{}',
                PublicKeyCredential::class,
            );

            $options = WebAuthn::fromJson($serialized, PublicKeyCredentialCreationOptions::class);

            $store(Auth::user(), $this->passkeyName, $publicKeyCredential, $options);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            // Logged, because the user only sees the generic line below and a
            // rejected origin or RP id would otherwise leave no trace.
            report($e);

            throw ValidationException::withMessages([
                'passkeyName' => ['That passkey could not be registered. Try again.'],
            ]);
        }

        $this->reset('passkeyName', 'passkeyOptions');
        $this->resetErrorBag();

        $this->refreshState();

        $this->dispatch('mfa-updated');
    }

    public function deletePasskey(int $passkeyId, DeletePasskey $delete): void
    {
        $this->authorizeChange();

        // Resolved through the signed-in user's own relation, so a crafted id can
        // only fail to match — it can never reach another account's credential.
        $passkey = Auth::user()->passkeys()->findOrFail($passkeyId);

        $delete(Auth::user(), $passkey);

        $this->refreshState();

        $this->dispatch('mfa-updated');
    }

    public function render()
    {
        return view('livewire.security.mfa-panel', [
            'requiresTotpConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ]);
    }
}
