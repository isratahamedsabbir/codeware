<div>
    {{--
        Colors here are driven by CSS custom properties (--form-*) with light
        defaults baked in, so this same component looks right unstyled on
        default/ecommerce and can be fully re-themed (e.g. dark, by the
        portfolio theme's .pf-contact-form scope) without editing this file.
    --}}
    <style>
        .contact-form-label { color: var(--form-label, #3f3f46); }
        .contact-form-field {
            background-color: var(--form-input-bg, #ffffff);
            border-color: var(--form-border, #d4d4d8);
            color: var(--form-text, #18181b);
        }
        .contact-form-field::placeholder { color: var(--form-placeholder, #a1a1aa); }
        .contact-form-field:focus {
            outline: none;
            border-color: var(--form-accent, var(--color-primary));
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--form-accent, var(--color-primary)) 20%, transparent);
        }
        .contact-form-submit { background-color: var(--form-accent, var(--color-primary)); }
        .contact-form-submit:hover { background-color: var(--form-accent-hover, var(--form-accent, var(--color-primary))); }
    </style>

    @if ($sent)
        <div class="rounded-2xl border p-8 text-center"
            style="border-color: var(--form-accent, #10b981); background-color: color-mix(in srgb, var(--form-accent, #10b981) 12%, transparent);">
            <h3 class="text-lg font-semibold" style="color: var(--form-text, #065f46)">{{ __('Message sent!') }}</h3>
            <p class="mt-2 text-sm" style="color: var(--form-label, #047857)">{{ __("Thanks for reaching out — we'll get back to you shortly.") }}</p>
            <button type="button" wire:click="$set('sent', false)"
                class="mt-4 text-sm font-medium underline" style="color: var(--form-accent, #047857)">
                {{ __('Send another message') }}
            </button>
        </div>
    @else
        <form wire:submit="send" class="space-y-5">
            <div>
                <label class="contact-form-label mb-1.5 block text-sm font-semibold">{{ __('Your Name') }}</label>
                <input type="text" wire:model="full_name" placeholder="{{ __('Your Name') }}"
                    class="contact-form-field w-full rounded-lg border px-3 py-2.5 text-sm transition" />
                @error('full_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="contact-form-label mb-1.5 block text-sm font-semibold">{{ __('Your Email') }}</label>
                <input type="email" wire:model="email" placeholder="name@example.com"
                    class="contact-form-field w-full rounded-lg border px-3 py-2.5 text-sm transition" />
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="contact-form-label mb-1.5 block text-sm font-semibold">{{ __('Your Message') }}</label>
                <textarea wire:model="message" rows="5" placeholder="{{ __('Tell me about your project...') }}"
                    class="contact-form-field w-full rounded-lg border px-3 py-2.5 text-sm transition"></textarea>
                @error('message') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="send"
                class="contact-form-submit flex w-full items-center justify-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60">
                <svg wire:loading.remove wire:target="send" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13" />
                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                </svg>
                <span wire:loading.remove wire:target="send">{{ __('Send Message') }}</span>
                <span wire:loading wire:target="send">{{ __('Sending...') }}</span>
            </button>
        </form>
    @endif
</div>
