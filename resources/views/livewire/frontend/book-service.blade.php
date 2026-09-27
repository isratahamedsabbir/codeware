<div>
    {{--
        Same CSS-custom-property contract as frontend.contact-form: the colors are
        driven by --form-* with light defaults, so this renders correctly on
        default/ecommerce unstyled and picks up the portfolio theme's dark form
        scope without this file knowing which theme it is in.

        The service is named in the heading rather than chosen from a select — a
        visitor books the card they are looking at, and a dropdown of the other
        services inside that card is a way to attach the request to the wrong one.
    --}}
    <style>
        .book-service-label { color: var(--form-label, #3f3f46); }
        .book-service-field {
            background-color: var(--form-input-bg, #ffffff);
            border-color: var(--form-border, #d4d4d8);
            color: var(--form-text, #18181b);
        }
        .book-service-field::placeholder { color: var(--form-placeholder, #a1a1aa); }
        .book-service-field:focus {
            outline: none;
            border-color: var(--form-accent, var(--color-primary));
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--form-accent, var(--color-primary)) 20%, transparent);
        }
        .book-service-submit { background-color: var(--form-accent, var(--color-primary)); }
        .book-service-submit:hover { background-color: var(--form-accent-hover, var(--form-accent, var(--color-primary))); }
    </style>

    @if ($service === null)
        {{-- The service was retired between the page render and this Livewire
             re-render. The form is withheld rather than shown with a blank
             service name, because every submit from here would be refused — an
             honest "gone" reads better than a form that cannot work. --}}
        <p class="rounded-card border p-6 text-center text-sm" style="border-color: var(--form-border, #d4d4d8); color: var(--form-label, #71717a)">
            {{ __('This service is no longer available.') }}
        </p>
    @elseif ($sent)
        <div class="rounded-card border p-8 text-center"
            style="border-color: var(--form-accent, #10b981); background-color: color-mix(in srgb, var(--form-accent, #10b981) 12%, transparent);">
            <h3 class="text-lg font-semibold" style="color: var(--form-text, #065f46)">{{ __('Request received!') }}</h3>
            <p class="mt-2 text-sm" style="color: var(--form-label, #047857)">
                {{ __('Thanks — this is a request to talk, not a confirmed booking. I\'ll be in touch to agree the details.') }}
            </p>
            <button type="button" wire:click="$set('sent', false)"
                class="mt-4 text-sm font-medium underline" style="color: var(--form-accent, #047857)">
                {{ __('Send another request') }}
            </button>
        </div>
    @else
        <form wire:submit="book" class="space-y-5">
            <p class="text-sm">
                {{ __('Request a call about') }}
                <span class="font-semibold">{{ $service?->name }}</span>
            </p>

            <div>
                <label class="book-service-label mb-1.5 block text-sm font-semibold">{{ __('Your Name') }}</label>
                <input type="text" wire:model="fullName" placeholder="{{ __('Your Name') }}"
                    class="book-service-field w-full rounded-lg border px-3 py-2.5 text-sm transition" />
                @error('fullName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="book-service-label mb-1.5 block text-sm font-semibold">{{ __('Your Email') }}</label>
                <input type="email" wire:model="email" placeholder="name@example.com"
                    class="book-service-field w-full rounded-lg border px-3 py-2.5 text-sm transition" />
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="book-service-label mb-1.5 block text-sm font-semibold">
                    {{ __('Phone Number') }}
                    <span class="font-normal opacity-70">{{ __('(optional)') }}</span>
                </label>
                <input type="tel" wire:model="phoneNumber" placeholder="+880 1XXX XXXXX"
                    class="book-service-field w-full rounded-lg border px-3 py-2.5 text-sm transition" />
                @error('phoneNumber') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="book-service-label mb-1.5 block text-sm font-semibold">
                    {{ __('Anything I should know?') }}
                    <span class="font-normal opacity-70">{{ __('(optional)') }}</span>
                </label>
                <textarea wire:model="message" placeholder="{{ __('Timeline, budget, or what you are trying to build.') }}"
                    class="book-service-field h-28 w-full rounded-lg border px-3 py-2.5 text-sm transition"></textarea>
                @error('message') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Spelled out rather than implied: a booking with no date on it is a
                 request to be contacted, and the visitor should leave knowing that
                 instead of discovering it when nothing is confirmed. --}}
            <p class="text-xs" style="color: var(--form-label, #71717a)">
                {{ __('This sends a request, not a confirmed appointment — no date is held until we agree one.') }}
            </p>

            <button type="submit" wire:loading.attr="disabled" wire:target="book"
                class="book-service-submit flex w-full items-center justify-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60">
                <svg wire:loading.remove wire:target="book" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13" />
                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                </svg>
                <span wire:loading.remove wire:target="book">{{ __('Request This Service') }}</span>
                <span wire:loading wire:target="book">{{ __('Sending...') }}</span>
            </button>
        </form>
    @endif
</div>
