@props([
    'icon' => 'squares-2x2',
    'title',
    'description' => null,
    'iconColor' => 'bg-primary/10 text-primary',
    'actions' => null,
    'bodyClass' => 'px-6 py-5 space-y-4',
    'headerBorder' => 'border-zinc-200',
    'collapsible' => false,
    'collapsed' => true,
])

<div {{ $attributes->class(['rounded-[5px] bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/40 overflow-hidden']) }}
    @if ($collapsible) x-data="{ open: {{ $collapsed ? 'false' : 'true' }} }" @endif>
    <div @if ($collapsible) role="button" tabindex="0" @click="open = !open" @keydown.enter="open = !open" @keydown.space.prevent="open = !open" @endif
        class="flex items-center justify-between gap-3 px-6 py-4 border-b {{ $headerBorder }} dark:border-zinc-700 {{ $collapsible ? 'cursor-pointer select-none' : '' }}">
        <div class="flex items-center gap-3 min-w-0">
            <div class="flex size-9 items-center justify-center rounded-lg {{ $iconColor }} shrink-0">
                <x-dynamic-component :component="'flux::icon.'.$icon" class="size-5" />
            </div>
            <div class="min-w-0">
                <flux:heading size="sm">{{ $title }}</flux:heading>
                @if ($description)
                    <flux:text class="text-xs text-zinc-500">{{ $description }}</flux:text>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @isset($actions)
                <div @if ($collapsible) @click.stop @endif>{{ $actions }}</div>
            @endisset
            @if ($collapsible)
                <button type="button" class="flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 cursor-pointer"
                    :aria-expanded="open.toString()" aria-label="Toggle section">
                    <flux:icon.chevron-down class="size-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                </button>
            @endif
        </div>
    </div>

    <div @if ($collapsible) x-show="open" x-collapse @endif class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
