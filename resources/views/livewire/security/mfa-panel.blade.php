{{--
    The one place a signed-in user manages their own second factors — TOTP in an
    authenticator app and WebAuthn passkeys, either of which answers the
    challenge (see App\Support\Mfa).

    Mounted from Admin → My Profile and from the forced-enrolment screen
    (App\Livewire\Security\MfaRequired), so this file has to render inside the
    admin panel's cards *and* inside layouts::auth on a bare tinted page. The
    only thing that changes between the two is $required, which adds the warning
    banner; everything else is identical, which is the point of sharing it.

    Two Alpine scopes:
      - the outer one owns the password-confirmation gate, because every action
        below can change or remove a factor and each of them calls
        authorizeChange() server-side;
      - the passkey registration block, which is the one part of this panel that
        is a multi-step ceremony — options out, credential back — and so needs
        somewhere to hold the authenticator's answer between two Livewire calls.
--}}
<div x-data="{ error: null }" class="space-y-5">

    @if ($required)
        <flux:callout variant="danger" icon="exclamation-triangle">
            {{ __('This portal requires a second sign-in factor. Set one up below to continue — nothing else here is reachable until you do.') }}
        </flux:callout>
    @endif

    {{-- Password confirmation, as a modal. Asked once per session, then every
         action stops asking (MfaPanel::authorizeChange()). --}}
    <div x-show="$wire.confirmPrompt" x-cloak
        x-on:keydown.escape.window="$wire.confirmPrompt && $wire.toggleConfirmPrompt()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog" aria-modal="true" aria-labelledby="mfa-confirm-title">
        <div class="absolute inset-0 bg-zinc-900/50 backdrop-blur-sm"
            x-show="$wire.confirmPrompt" x-transition.opacity
            x-on:click="$wire.toggleConfirmPrompt()"></div>

        <div class="relative w-full max-w-md overflow-hidden rounded-xl border border-zinc-200 bg-white text-zinc-900 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            x-show="$wire.confirmPrompt" x-transition.scale.95.opacity>
            <form wire:submit="confirmPasswordAction">
                <div class="flex items-start gap-3 px-6 pt-6">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300">
                        <flux:icon.key class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <h3 id="mfa-confirm-title" class="text-base font-semibold">{{ __('Confirm your password') }}</h3>
                        <p class="mt-0.5 text-sm text-zinc-500">{{ __('Needed before a second factor can be added or removed.') }}</p>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <flux:field>
                        <flux:label>{{ __('Current password') }}</flux:label>
                        <flux:input wire:model="current_password" type="password" autocomplete="current-password" viewable
                            x-init="$watch('$wire.confirmPrompt', (open) => { if (open) $nextTick(() => $el.querySelector('input')?.focus()) })" />
                        <flux:error name="current_password" />
                    </flux:field>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50/70 px-6 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <flux:button size="sm" variant="ghost" type="button" wire:click="toggleConfirmPrompt">{{ __('Cancel') }}</flux:button>
                    <flux:button size="sm" variant="primary" type="submit">{{ __('Confirm') }}</flux:button>
                </div>
            </form>
        </div>
    </div>

    <div wire:key="mfa-panel-body" class="flex flex-col gap-5">

        {{-- ── Authenticator app (TOTP) ───────────────────────────────── --}}
        @if ($totpSupported)
            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                <header class="flex flex-wrap items-center gap-3 border-b border-zinc-100 bg-zinc-50/70 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300">
                        <flux:icon.device-phone-mobile class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Authenticator app') }}</h3>
                            @if ($totpEnabled)
                                <span class="inline-flex items-center gap-1 rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-700">
                                    <span class="size-1.5 rounded-full bg-green-500"></span>{{ __('On') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600">
                                    <span class="size-1.5 rounded-full bg-zinc-400"></span>{{ __('Off') }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ __('A 6-digit code from an app on your phone, at every sign-in.') }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($totpEnabled)
                            <flux:button size="sm" variant="danger" wire:click="disableTotp"
                                wire:confirm="Turn off the authenticator app? Anyone with just your password will be able in.">
                                {{ __('Turn off') }}
                            </flux:button>
                        @elseif (! $verifyingTotp)
                            <flux:button size="sm" variant="primary" wire:click="startTotpSetup">
                                {{ __('Turn on') }}
                            </flux:button>
                        @endif
                    </div>
                </header>
                <div class="space-y-4 px-5 py-5 text-sm">

                <flux:error name="totp" />

                @if ($verifyingTotp)
                    {{-- Step 2 of enrolment: prove the secret actually works,
                         otherwise the account would carry a factor it cannot
                         answer a challenge with. --}}
                    <div class="space-y-5" wire:key="totp-verify">
                        <div class="mx-auto w-fit rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-100"
                            x-data>
                            <div :style="($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'filter: invert(1) brightness(1.5)' : ''">
                                {!! $qrCodeSvg !!}
                            </div>
                        </div>

                        <div class="text-center">
                            <p class="text-xs text-zinc-500">
                                {{ __('Scan this with your authenticator app, or enter the key by hand:') }}
                            </p>
                            <code class="mt-1.5 inline-block select-all rounded bg-zinc-100 px-2 py-1 font-mono text-xs dark:bg-zinc-800">
                                {{ $manualSetupKey }}
                            </code>
                        </div>

                        <form wire:submit="confirmTotp" class="mx-auto max-w-xs space-y-4 text-center">
                            <div class="flex justify-center">
                                <flux:otp wire:model="code" length="6" name="code" label="OTP Code" label:sr-only />
                            </div>
                            <flux:error name="code" />

                            <div class="flex items-center justify-center gap-3">
                                <flux:button size="sm" variant="primary" type="submit"
                                    x-bind:disabled="$wire.code.length < 6">
                                    {{ __('Confirm') }}
                                </flux:button>
                                <flux:button size="sm" variant="ghost" type="button" wire:click="cancelTotpSetup">
                                    {{ __('Start over') }}
                                </flux:button>
                            </div>
                        </form>
                    </div>
                @elseif ($totpEnabled)
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                        {{ __('On. You will be asked for a 6-digit code at every sign-in, here and on the public site.') }}
                    </p>

                    {{-- Recovery codes: the way back in when the phone is gone.
                         Eight single-use codes, so this is the only part of the
                         panel whose contents must actually be written down —
                         hence the plain list and the copy button rather than
                         anything to screenshot away silently. --}}
                    <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:error name="recoveryCodes" />

                        @if ($showingRecoveryCodes && filled($recoveryCodes))
                            <div class="space-y-3" wire:key="recovery-codes">
                                <flux:callout variant="warning" icon="exclamation-triangle">
                                    {{ __('Each code works once. This is the only time they are shown — copy them somewhere safe now.') }}
                                </flux:callout>

                                <ul class="grid grid-cols-2 gap-1.5 font-mono text-xs sm:grid-cols-4">
                                    @foreach ($recoveryCodes as $recoveryCode)
                                        <li wire:key="recovery-code-{{ $loop->index }}"
                                            class="select-all rounded bg-zinc-100 px-2 py-1 text-center dark:bg-zinc-800">
                                            {{ $recoveryCode }}
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Copy, inline rather than via <x-copy-text> because that component's
                                     classes are built for a text link inside a value,
                                     and this needs to sit in a row of buttons. Same
                                     insecure-context fallback it carries, which
                                     matters here more than anywhere: the admin
                                     panel runs over plain HTTP on .test locally. --}}
                                    <button type="button"
                                        x-data="{
        copied: false,
        copy() {
            const codes = @js($recoveryCodes);
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(codes.join('\n'));
            } else {
                const ta = document.createElement('textarea');
                ta.value = codes.join('\n');
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta);
            }
            this.copied = true;
            setTimeout(() => this.copied = false, 1500);
        }
    }"
                                        x-on:click="copy()"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500 cursor-pointer">
                                        <span x-text="copied ? @js(__('Copied!')) : @js(__('Copy all codes'))"></span>
                                    </button>

                                    <flux:button size="sm" variant="ghost" wire:click="hideRecoveryCodes">
                                        {{ __("I've saved them") }}
                                    </flux:button>
                            </div>
                        @else
                            <div class="flex flex-wrap items-center gap-3">
                                <flux:button size="sm" variant="outline" wire:click="showRecoveryCodes">
                                    {{ __('View recovery codes') }}
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="generateRecoveryCodes"
                                    wire:confirm="Replace all eight recovery codes? The current ones stop working.">
                                    {{ __('Generate new ones') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-zinc-500">
                        {{ __('Off. Turn it on to be asked for a 6-digit code from an authenticator app at every sign-in.') }}
                    </p>
                @endif
            </div>
            </section>
        @endif

        {{-- ── Passkeys (WebAuthn) ─────────────────────────────────────── --}}
        @if ($passkeysSupported)
            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                <header class="flex flex-wrap items-center gap-3 border-b border-zinc-100 bg-zinc-50/70 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300">
                        <flux:icon.finger-print class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Passkeys') }}</h3>
                            @if ($passkeysEnabled)
                                <span class="inline-flex items-center gap-1 rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-700">
                                    <span class="size-1.5 rounded-full bg-green-500"></span>{{ __('On') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600">
                                    <span class="size-1.5 rounded-full bg-zinc-400"></span>{{ __('Off') }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Sign in with your fingerprint, face or device PIN — no code to type.') }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <flux:button size="sm" :variant="$passkeysEnabled ? 'outline' : 'primary'"
                            wire:click="$set('addingPasskey', true); $set('passkeyOptions','')">
                            {{ __('Add passkey') }}
                        </flux:button>
                    </div>
                </header>
                <div class="space-y-4 px-5 py-5 text-sm">

                @unless ($passkeysEnabled)
                    <p class="text-sm text-zinc-500">
                        {{ __('None registered. A passkey is a key stored in your device or password manager, used to approve a sign-in.') }}
                    </p>
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->passkeys as $passkey)
                            <li wire:key="passkey-{{ $passkey['id'] }}"
                                class="flex items-center justify-between gap-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ $passkey['name'] }}
                                    </p>
                                    <p class="text-xs text-zinc-400">
                                        {{ $passkey['last_used_at']
                                            ? __('Last used :date', ['date' => $passkey['last_used_at']])
                                            : __('Never used') }}
                                    </p>
                                </div>
                                <button type="button" wire:click="deletePasskey({{ $passkey['id'] }})"
                                    wire:confirm="Remove this passkey?"
                                    class="shrink-0 text-xs font-medium text-red-600 hover:text-red-700 cursor-pointer">
                                    {{ __('Remove') }}
                                </button>
                            </li>
                        @empty
                            <li class="py-2.5 text-sm text-zinc-500">{{ __('None registered.') }}</li>
                        @endforelse
                    </ul>
                @endunless

                {{-- The ceremony. options go out from a Livewire call, the
                     authenticator answers in the browser, and the credential
                     comes back into addPasskey(). Livewire's action wrappers
                     already put a CSRF token on fetch(), which is the only auth
                     this post needs — there is no form to submit. --}}
                <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800"
                    x-data="{
                        busy: false,
                        error: null,
                        available: window.Passkeys ? window.Passkeys.supported() : false,

                        async start() {
                            this.busy = true;
                            this.error = null;

                            await this.$wire.passkeyRegistrationOptions();

                            if (! this.$wire.passkeyOptions) {
                                this.busy = false;
                                return;
                            }

                            const result = await window.Passkeys.create(this.$wire.passkeyOptions);

                            if (result.error) {
                                this.error = result.error;
                            } else {
                                await this.$wire.addPasskey(result.credential);
                            }

                            this.busy = false;
                        },
                    }"
                    x-show="$wire.addingPasskey" x-cloak
                    x-on:mfa-updated.window="$wire.addingPasskey = false; $wire.passkeyOptions = ''">

                    <template x-if="! available">
                        <flux:callout variant="secondary" icon="exclamation-triangle">
                            {{ __('This browser cannot register a passkey. Use the authenticator app option instead.') }}
                        </flux:callout>
                    </template>

                    <form class="mt-3 space-y-3" x-on:submit.prevent="start()">
                        <flux:field>
                            <flux:label>Name this passkey</flux:label>
                            <flux:input x-model="$wire.passkeyName" placeholder="MacBook Pro" maxlength="255" />
                            <flux:error name="passkeyName" />
                        </flux:field>

                        <div class="flex items-center gap-3">
                            <flux:button size="sm" variant="primary" type="submit" x-bind:disabled="busy || ! available">
                                <span x-show="! busy">{{ __('Create passkey') }}</span>
                                <span x-show="busy">{{ __('Waiting for your device…') }}</span>
                            </flux:button>
                            <button type="button" x-on:click="$wire.addingPasskey = false"
                                class="text-xs text-zinc-500 hover:text-zinc-700 cursor-pointer">
                                Cancel
                            </button>
                        </div>
                    </form>

                    <p x-show="error" x-cloak x-text="error" class="mt-2 text-xs text-red-600"></p>
                </div>
                </div>
            </section>
        @endif

        @unless ($totpSupported || $passkeysSupported)
            <flux:callout variant="secondary" icon="exclamation-triangle">
                {{ __('Multi-factor authentication is switched off in this installation (see config/fortify.php).') }}
            </flux:callout>
        @endunless
    </div>
</div>