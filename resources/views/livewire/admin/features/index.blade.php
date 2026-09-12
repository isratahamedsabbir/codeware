<div class="max-w-[1600px] space-y-6">
    <flux:text class="text-zinc-500">
        {{ __('This admin panel is reused as a starting point across different projects — turn off anything this project doesn\'t need. Disabled features are hidden from the sidebar and their pages become unreachable.') }}
    </flux:text>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach (\App\Support\Features::ALL as $key => $label)
            <label class="flex items-center justify-between gap-4 px-5 py-4 rounded-lg bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/40 cursor-pointer">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __($label) }}</span>
                <input type="checkbox" wire:model="features.{{ $key }}"
                    class="rounded border-zinc-300 text-primary" />
            </label>
        @endforeach
    </div>

    <div>
        <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
            Save Features
        </flux:button>
    </div>
</div>
