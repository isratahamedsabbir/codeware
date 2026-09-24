<form wire:submit="save" class="space-y-5">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-semibold text-zinc-700">
            {{ __('Name') }}
        </label>
        <input id="name" wire:model="name" type="text" required autocomplete="name"
            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
        @error('name')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="mb-1.5 block text-sm font-semibold text-zinc-700">
            {{ __('Email') }}
        </label>
        <input id="email" wire:model="email" type="email" required autocomplete="email"
            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
        @error('email')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror

        @if ($this->hasUnverifiedEmail())
            <p class="mt-2 text-sm text-amber-600">
                {{ __('Your email address is unverified.') }}
                <button type="button" wire:click="resendVerificationNotification"
                    class="font-semibold underline underline-offset-2 hover:opacity-80">
                    {{ __('Click here to re-send the verification email.') }}
                </button>
            </p>

            @if (session('status') === 'verification-link-sent')
                <p class="mt-2 text-sm text-green-600">
                    {{ __('A new verification link has been sent to your email address.') }}
                </p>
            @endif
        @endif
    </div>

    <hr class="border-zinc-200">

    <div>
        <h3 class="text-sm font-bold text-sf-text">{{ __('Change password') }}</h3>
        <p class="mt-0.5 text-xs text-zinc-500">{{ __('Leave these blank to keep your current password.') }}</p>
    </div>

    <div>
        <label for="current_password" class="mb-1.5 block text-sm font-semibold text-zinc-700">
            {{ __('Current password') }}
        </label>
        <input id="current_password" wire:model="current_password" type="password" autocomplete="current-password"
            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
        @error('current_password')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="mb-1.5 block text-sm font-semibold text-zinc-700">
            {{ __('New password') }}
        </label>
        <input id="password" wire:model="password" type="password" autocomplete="new-password"
            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
        @error('password')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-zinc-700">
            {{ __('Confirm new password') }}
        </label>
        <input id="password_confirmation" wire:model="password_confirmation" type="password" autocomplete="new-password"
            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-sf-text outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20">
    </div>

    <div class="flex items-center gap-3 pt-1" x-data="{ saved: false }" x-on:notify.window="saved = $event.detail.message === 'Profile updated.'; $timeout(() => saved = false, 3000)">
        <button type="submit"
            class="rounded-full bg-sf-button px-6 py-2.5 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
            {{ __('Save') }}
        </button>
        <span x-show="saved" x-cloak class="text-sm font-medium text-green-600">{{ __('Saved.') }}</span>
    </div>
</form>