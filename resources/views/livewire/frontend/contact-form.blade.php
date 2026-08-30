<div class="mx-auto max-w-xl px-6 py-16">
    @if ($sent)
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-8 text-center">
            <h3 class="text-lg font-semibold text-emerald-800">{{ __('Message sent!') }}</h3>
            <p class="mt-2 text-sm text-emerald-700">{{ __("Thanks for reaching out — we'll get back to you shortly.") }}</p>
            <button type="button" wire:click="$set('sent', false)"
                class="mt-4 text-sm font-medium text-emerald-700 underline hover:text-emerald-900">
                {{ __('Send another message') }}
            </button>
        </div>
    @else
        <form wire:submit="send" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-zinc-700">{{ __('Full Name') }}</label>
                    <input type="text" wire:model="full_name"
                        class="mt-1.5 w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" />
                    @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700">{{ __('Phone Number') }}</label>
                    <input type="text" wire:model="phone_number"
                        class="mt-1.5 w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" />
                    @error('phone_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Email') }}</label>
                <input type="email" wire:model="email"
                    class="mt-1.5 w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" />
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Subject') }}</label>
                <input type="text" wire:model="subject"
                    class="mt-1.5 w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" />
                @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Message') }}</label>
                <textarea wire:model="message" rows="5"
                    class="mt-1.5 w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="send"
                class="w-full rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60">
                <span wire:loading.remove wire:target="send">{{ __('Send Message') }}</span>
                <span wire:loading wire:target="send">{{ __('Sending...') }}</span>
            </button>
        </form>
    @endif
</div>
