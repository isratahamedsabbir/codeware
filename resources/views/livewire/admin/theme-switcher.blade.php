<div>
    <flux:button variant="subtle" square wire:click="toggle"
        :title="$dark ? 'Switch to light mode' : 'Switch to dark mode'"
        :aria-label="$dark ? 'Switch to light mode' : 'Switch to dark mode'"
        class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors">
        @if ($dark)
            <flux:icon.sun class="size-5" />
        @else
            <flux:icon.moon class="size-5" />
        @endif
    </flux:button>
</div>
