<div>
    @if ($sent)
        <div class="rounded-card border border-emerald-300 bg-emerald-50 p-8 text-center">
            <h3 class="text-lg font-semibold text-emerald-800">{{ __('Application submitted!') }}</h3>
            <p class="mt-2 text-sm text-emerald-700">{{ __('Thanks for applying — our team will review your application and get in touch.') }}</p>
        </div>
    @else
        <form wire:submit="apply" class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-zinc-700">{{ __('Full name') }}</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm focus:border-brand focus:outline-none" />
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-zinc-700">{{ __('Email') }}</label>
                <input type="email" wire:model="email" class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm focus:border-brand focus:outline-none" />
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-zinc-700">{{ __('Phone') }}</label>
                <input type="text" wire:model="phone" class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm focus:border-brand focus:outline-none" />
                @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-zinc-700">{{ __('Résumé / CV') }} <span class="font-normal text-zinc-400">(PDF, DOC, DOCX · max 5 MB)</span></label>
                <input type="file" wire:model="resume" accept=".pdf,.doc,.docx"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand/10 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand" />
                <div wire:loading wire:target="resume" class="mt-1 text-xs text-zinc-500">{{ __('Uploading…') }}</div>
                @error('resume') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-zinc-700">{{ __('Cover letter') }} <span class="font-normal text-zinc-400">({{ __('optional') }})</span></label>
                <textarea wire:model="cover_letter" rows="4" class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm focus:border-brand focus:outline-none"></textarea>
                @error('cover_letter') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="apply,resume"
                class="flex w-full items-center justify-center rounded-lg bg-brand px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60">
                <span wire:loading.remove wire:target="apply">{{ __('Submit application') }}</span>
                <span wire:loading wire:target="apply">{{ __('Submitting…') }}</span>
            </button>
        </form>
    @endif
</div>
