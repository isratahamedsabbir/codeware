<button type="button" wire:click="toggle"
    @if ($enabled) wire:confirm="Turn off the shop? Customers won't be able to place new orders until you turn it back on." @endif
    title="{{ $enabled ? __('Shop is ON — click to turn off') : __('Shop is OFF — click to turn on') }}"
    aria-label="{{ $enabled ? __('Turn shop off') : __('Turn shop on') }}"
    class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-lg text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-primary/30 {{ $enabled
        ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-400'
        : 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950 dark:text-red-400' }}">
    <span class="size-1.5 rounded-full {{ $enabled ? 'bg-emerald-500' : 'bg-red-500 animate-pulse' }}"></span>
    <flux:icon.building-storefront class="size-4" />
    {{ $enabled ? __('Shop On') : __('Shop Off') }}
</button>
