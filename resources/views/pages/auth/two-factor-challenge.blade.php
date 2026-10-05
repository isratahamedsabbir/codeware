@php
    // Whether to offer a passkey as a second way in. Asked of App\Support\Mfa
    // rather than read off $errors, because the page has no idea an account may
    // hold a passkey at all — Fortify's view only ever knew about TOTP. An
    // account with *only* a passkey is the case that matters: it has nothing to
    // type into the field below, so without this it would be stuck.
    $canUsePasskey = \App\Support\Mfa::challengedUserHasPasskey();
@endphp

{{-- :passkeys loads the WebAuthn client (see layouts/auth/split.blade.php). --}}
<x-layouts::auth :title="__('Two-factor authentication')" :passkeys="$canUsePasskey">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',

                // ── Passkey path ─────────────────────────────────────────
                //
                // A WebAuthn assertion is a ceremony, not a value: the browser
                // has to be handed the challenge, the user has to approve it on
                // the device, and only then does anything get posted. The TOTP
                // form below cannot carry that, which is why this is a separate
                // request path rather than another input on the same one.
                passkeyBusy: false,
                passkeyError: null,

                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $dispatch('clear-2fa-auth-code');

                    this.$nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : $dispatch('focus-2fa-auth-code');
                    });
                },

                csrf() {
                    return document.querySelector('meta[name=csrf-token]')?.content
                        ?? @js(csrf_token());
                },

                async usePasskey() {
                    if (!window.Passkeys || !window.Passkeys.supported()) {
                        this.passkeyError = @json(__('This browser cannot use a passkey. Use the code instead.'));

                        return;
                    }

                    this.passkeyBusy = true;
                    this.passkeyError = null;

                    try {
                        // Options first. They are kept server-side too — the
                        // verifier checks the assertion against the challenge it
                        // issued — so this response is only for the browser.
                        const optionsResponse = await fetch(
                            @js(route('two-factor.passkey-options')),
                            { headers: { Accept: 'application/json' }, credentials: 'same-origin' },
                        );

                        if (!optionsResponse.ok) {
                            this.passkeyError = @json(__('Could not start the passkey check. Try again.'));

                            return;
                        }

                        const { options } = await optionsResponse.json();

                        const result = await window.Passkeys.get(options);

                        if (result.error) {
                            // "Cancelled." is what the client says when the user
                            // dismissed the device prompt, which is not worth
                            // dressing up as an error.
                            this.passkeyError = result.error;

                            return;
                        }

                        const verifyResponse = await fetch(
                            @js(route('two-factor.passkey-verify')),
                            {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                                body: JSON.stringify({ credential: result.credential }),
                            },
                        );

                        if (!verifyResponse.ok) {
                            const payload = await verifyResponse.json().catch(() => ({}));

                            this.passkeyError = payload?.errors?.credential?.[0]
                                ?? @json(__('That passkey was not accepted. Try again, or use another method.'));

                            return;
                        }

                        // The controller replies with the same redirect a typed
                        // code gets, so follow it and leave the form behind.
                        window.location.href = verifyResponse.redirected
                            ? verifyResponse.url
                            : @js(url('/'));
                    } catch (error) {
                        this.passkeyError = @json(__('Something went wrong. Try again, or use another method.'));
                    } finally {
                        this.passkeyBusy = false;
                    }
                },
            }"
        >
            <div x-show="!showRecoveryInput">
                <x-auth-header
                    :title="__('Authentication code')"
                    :description="__('Enter the authentication code provided by your authenticator application.')"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="__('Recovery code')"
                    :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
                />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="space-y-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="flex items-center justify-center my-5">
                            <flux:otp
                                x-model="code"
                                length="6"
                                name="code"
                                label="OTP Code"
                                label:sr-only
                                class="mx-auto"
                             />
                        </div>
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-5">
                            <flux:input
                                type="text"
                                name="recovery_code"
                                x-ref="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                            />
                        </div>

                        @error('recovery_code')
                            <flux:text color="red">
                                {{ $message }}
                            </flux:text>
                        @enderror
                    </div>

                    <flux:button size="sm"
                        variant="primary"
                        type="submit"
                        class="w-full"
                    >
                        {{ __('Continue') }}
                    </flux:button>
                </div>

                @if ($canUsePasskey)
                    {{-- The passkey route. Divider is inside the gate so an
                         account with no passkey sees exactly the page it saw
                         before this existed. --}}
                    <div class="mt-6 space-y-3">
                        <div class="flex items-center gap-3 text-xs text-zinc-400">
                            <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></span>
                            <span>{{ __('or') }}</span>
                            <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></span>
                        </div>

                        <flux:button size="sm"
                            variant="outline"
                            type="button"
                            class="w-full"
                            x-on:click="usePasskey()"
                            x-bind:disabled="passkeyBusy"
                        >
                            <span x-show="! passkeyBusy">{{ __('Use a passkey') }}</span>
                            <span x-show="passkeyBusy" x-cloak>{{ __('Waiting for your device…') }}</span>
                        </flux:button>

                        <p x-show="passkeyError" x-cloak x-text="passkeyError"
                            class="text-center text-xs text-red-600"></p>
                    </div>
                @endif

                <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                    <span class="opacity-50">{{ __('or you can') }}</span>
                    <div class="inline font-medium underline cursor-pointer opacity-80">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>