<div>
    {{--
        Same contract as the shared contact form: colours come from CSS custom
        properties (--form-*) rather than baked-in utilities, so the same
        component can sit on the ecommerce theme's dark footer and on the
        portfolio theme's light one without either theme editing this file.

        The colour fallbacks below are the ecommerce footer's dark values, so a
        footer with no scope on it keeps the colours it has always had. The
        portfolio theme re-points them at its own light/dark tokens in
        style.css (.pf-newsletter).
    --}}
    <style>
        /* The pill shape is restated here rather than left to the rounded-full
           utility: app.css sets a bare `input { border-radius: 5px }` and
           `button { border-radius: 5px }` at the top level, and unlayered rules
           outrank Tailwind's layered utilities whatever their order — so the
           utility silently loses and both the field and the button come out
           square. This block is unlayered too, so it wins instead. */
        .newsletter-field,
        .newsletter-submit {
            border-radius: 999px;
        }

        .newsletter-field {
            background-color: var(--form-input-bg, rgba(255, 255, 255, 0.1));
            border-color: var(--form-border, rgba(255, 255, 255, 0.2));
            color: var(--form-text, #ffffff);
        }

        .newsletter-field::placeholder { color: var(--form-placeholder, rgba(255, 255, 255, 0.5)); }

        .newsletter-field:focus {
            outline: none;
            background-color: var(--form-input-bg-focus, rgba(255, 255, 255, 0.15));
            border-color: var(--form-border-focus, rgba(255, 255, 255, 0.5));
        }

        .newsletter-icon { color: var(--form-placeholder, rgba(255, 255, 255, 0.5)); }

        .newsletter-submit {
            background-color: var(--form-accent, #ffffff);
            color: var(--form-submit-text, var(--color-brand));
        }

        .newsletter-submit:hover {
            background-color: var(--form-accent-hover, var(--color-brand-ink));
            color: var(--form-submit-text-hover, #ffffff);
        }

        .newsletter-done {
            background-color: var(--form-success-bg, rgba(255, 255, 255, 0.1));
            color: var(--form-success-text, #ffffff);
        }

        .newsletter-done-icon { color: var(--form-success-icon, #34d399); }

        .newsletter-error { color: var(--form-error, #fca5a5); }
    </style>

    @if ($done)
        <div class="newsletter-done flex items-center gap-2 rounded-full px-4 py-3 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="newsletter-done-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>
                {{ $already ? __('You\'re already subscribed!') : __('Thanks for subscribing!') }}
            </span>
        </div>
    @else
        <form wire:submit="subscribe" class="newsletter-form flex w-full flex-col gap-2 sm:flex-row">
            <div class="relative flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="newsletter-icon pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
                <input type="email" wire:model="email" wire:loading.attr="disabled" wire:target="subscribe"
                    placeholder="{{ __('Your email address') }}" aria-label="{{ __('Your email address') }}" autocomplete="email"
                    class="newsletter-field w-full rounded-full border py-2.5 pl-10 pr-4 text-sm transition focus:outline-none disabled:opacity-60" />
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="subscribe"
                class="newsletter-submit shrink-0 rounded-full px-5 py-2.5 text-sm font-bold transition disabled:opacity-60">
                <span wire:loading.remove wire:target="subscribe">{{ __('Subscribe') }}</span>
                <span wire:loading wire:target="subscribe">{{ __('Subscribing...') }}</span>
            </button>
        </form>
        @error('email') <p class="newsletter-error mt-1.5 text-xs font-medium">{{ $message }}</p> @enderror
    @endif
</div>
