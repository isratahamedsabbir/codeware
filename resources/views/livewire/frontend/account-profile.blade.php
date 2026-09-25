<form wire:submit="save" class="space-y-5">
    {{-- Profile photo: a picked file previews straight away; nothing is stored
         until the form is saved. --}}
    @php
        $user = auth()->user();
        $previewUrl = match (true) {
            $photo && ! $errors->has('photo') => $photo->temporaryUrl(),
            $removePhoto => null,
            default => $user->photo_url,
        };
    @endphp
    <div class="flex flex-wrap items-center gap-5">
        <label for="profile-photo" class="group relative block size-20 shrink-0 cursor-pointer overflow-hidden rounded-full bg-brand/10 ring-4 ring-white shadow-md">
            @if ($previewUrl)
                <img src="{{ $previewUrl }}" alt="{{ $user->name }}" class="size-full object-cover">
            @else
                <span class="flex size-full items-center justify-center text-2xl font-bold uppercase text-brand">{{ $user->initials() }}</span>
            @endif
            <span class="absolute inset-0 flex items-center justify-center bg-black/45 text-white opacity-0 transition group-hover:opacity-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                </svg>
            </span>
            <span wire:loading.flex wire:target="photo" class="absolute inset-0 items-center justify-center bg-white/80">
                <svg class="size-6 animate-spin text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                </svg>
            </span>
        </label>

        <div class="min-w-0">
            <p class="text-sm font-semibold text-sf-text">{{ __('Profile photo') }}</p>
            <p class="mt-0.5 text-xs text-zinc-500">{{ __('JPG, PNG or WebP, up to 2 MB.') }}</p>
            <div class="mt-2.5 flex flex-wrap items-center gap-2">
                <label for="profile-photo"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-sm transition hover:border-brand hover:text-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    {{ $previewUrl ? __('Change photo') : __('Upload photo') }}
                </label>
                @if ($previewUrl)
                    <button type="button" wire:click="removeCurrentPhoto"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold text-zinc-500 transition hover:bg-red-50 hover:text-red-600">
                        {{ __('Remove') }}
                    </button>
                @endif
            </div>
            @if ($photo || $removePhoto)
                <p class="mt-1.5 text-xs font-medium text-amber-600">{{ __('Save changes to apply your new photo.') }}</p>
            @endif
        </div>

        <input id="profile-photo" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp,image/avif" class="sr-only">
    </div>
    @error('photo')
        <p class="-mt-2 text-xs text-red-600">{{ $message }}</p>
    @enderror

    <hr class="border-zinc-200">

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