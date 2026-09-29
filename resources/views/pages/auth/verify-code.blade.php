<x-layouts::auth :title="__('Enter your code')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Enter your code')" :description="__('We sent a six-digit code to :email. Enter it below to choose a new password.', ['email' => $email])" />

        <form method="POST" action="{{ route('password.verify.check') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="code"
                :label="__('Verification code')"
                type="text"
                inputmode="numeric"
                maxlength="6"
                required
                autofocus
                autocomplete="one-time-code"
                placeholder="000000"
            />

            <flux:button size="sm" type="submit" variant="primary" class="w-full" data-test="verify-code-button">
                {{ __('Continue') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Wrong address, or no code yet?') }}</span>
            <flux:link :href="route('password.request')" wire:navigate>{{ __('Send it again') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
