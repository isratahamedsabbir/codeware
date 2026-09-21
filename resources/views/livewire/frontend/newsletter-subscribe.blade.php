<div>
    @if ($done)
        <div class="flex items-center gap-2 rounded-full bg-white/10 px-4 py-3 text-sm text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>
                {{ $already ? __('You\'re already subscribed!') : __('Thanks for subscribing!') }}
            </span>
        </div>
    @else
        <form wire:submit="subscribe" class="flex w-full flex-col gap-2 sm:flex-row">
            <div class="relative flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
                <input type="email" wire:model="email" wire:loading.attr="disabled" wire:target="subscribe"
                    placeholder="{{ __('Your email address') }}" autocomplete="email"
                    class="w-full rounded-full border border-white/20 bg-white/10 py-2.5 pl-10 pr-4 text-sm text-white placeholder:text-white/50 transition focus:border-white/50 focus:bg-white/15 focus:outline-none disabled:opacity-60" />
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="subscribe"
                class="shrink-0 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-brand transition hover:bg-brand-ink hover:text-white disabled:opacity-60">
                <span wire:loading.remove wire:target="subscribe">{{ __('Subscribe') }}</span>
                <span wire:loading wire:target="subscribe">{{ __('Subscribing...') }}</span>
            </button>
        </form>
        @error('email') <p class="mt-1.5 text-xs font-medium text-red-300">{{ $message }}</p> @enderror
    @endif
</div>