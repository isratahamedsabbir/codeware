@props([
    'icon' => 'squares-2x2',
    'title',
    'description' => null,
    'iconColor' => 'bg-primary/10 text-primary',
    'actions' => null,
    'bodyClass' => null,
    'headerBorder' => 'border-zinc-200',
    // Drops the outline and the shadow, and the header divider with them, so the
    // section reads as a clean tinted block rather than a bordered, shadowed
    // box. The surface stays: on a tinted admin page a section with no
    // background at all loses the fill the form fields are read against. Off by
    // default, so every other admin screen keeps its full framing.
    'plain' => false,
    'collapsible' => null,
    'collapsed' => true,
    // 'default' = icon + description header; 'postbox' = WordPress-style metabox
    // (bordered title bar with the description under the title, collapsible unless
    // :collapsible="false" is passed).
    'variant' => 'default',
    // Postbox only: when set, the open/closed state is remembered per browser.
    'persistKey' => null,
])

@php
    $postbox = $variant === 'postbox';
    $collapsible = (bool) ($collapsible ?? $postbox);
    $bodyClass ??= $postbox ? 'p-3 space-y-3' : 'px-6 py-5 space-y-3';
    // A non-collapsible postbox is always open.
    $openDefault = ($collapsible && $collapsed) ? 'false' : 'true';
    $openExpr = ($collapsible && $persistKey) ? "\$persist({$openDefault}).as('postbox:{$persistKey}')" : $openDefault;
@endphp

@if ($postbox)
    <div {{ $attributes->class($plain
        ? 'admin-postbox rounded-[3px] bg-white dark:bg-zinc-800/40'
        : 'admin-postbox rounded-[3px] bg-white shadow-[0_1px_1px_rgba(0,0,0,0.04)] dark:bg-zinc-800/40 border border-zinc-300 dark:border-zinc-700') }}
        x-data="{ open: {{ $openExpr }} }">
        <div @if ($collapsible) role="button" tabindex="0" @click="open = !open" @keydown.enter="open = !open" @keydown.space.prevent="open = !open" @endif
            class="flex min-h-11 items-center justify-between gap-3 px-3 py-2 {{ $collapsible ? 'cursor-pointer select-none' : '' }}"
            :class="open ? '{{ $plain ? '' : 'border-b border-zinc-300 dark:border-zinc-700' }}' : ''">
            <div class="min-w-0">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $title }}</h2>
                    @isset($titleActions)
                        <span class="flex items-center" @click.stop>{{ $titleActions }}</span>
                    @endisset
                </div>
                @if ($description)
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-1">
                @isset($actions)
                    <div @click.stop>{{ $actions }}</div>
                @endisset
                @if ($collapsible)
                    <button type="button" class="flex size-8 cursor-pointer items-center justify-center rounded text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-700"
                        :aria-expanded="open.toString()" aria-label="Toggle panel: {{ $title }}">
                        <flux:icon.chevron-up variant="mini" class="size-5 transition-transform" x-bind:class="open ? '' : 'rotate-180'" />
                    </button>
                @endif
            </div>
        </div>

        <div x-show="open" x-collapse @if ($openDefault === 'false') x-cloak @endif>
            <div class="{{ $bodyClass }}">
                {{ $slot }}
            </div>
        </div>
    </div>
@else
<div {{ $attributes->class($plain
    ? 'rounded-[5px] bg-white dark:bg-zinc-800/40 overflow-hidden'
    : 'rounded-[5px] bg-white shadow-sm dark:bg-zinc-800/40 overflow-hidden border border-zinc-200 dark:border-zinc-700') }}
    @if ($collapsible) x-data="{ open: {{ $collapsed ? 'false' : 'true' }} }" @endif>
    <div @if ($collapsible) role="button" tabindex="0" @click="open = !open" @keydown.enter="open = !open" @keydown.space.prevent="open = !open" @endif
        class="flex items-center justify-between gap-3 px-6 py-4 {{ $plain ? '' : 'border-b '.$headerBorder.' dark:border-zinc-700 ' }} {{ $collapsible ? 'cursor-pointer select-none' : '' }}">
        <div class="flex items-center gap-3 min-w-0">
            <div class="flex size-9 items-center justify-center rounded-lg {{ $iconColor }} shrink-0">
                <x-dynamic-component :component="'flux::icon.'.$icon" class="size-5" />
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <flux:heading size="sm">{{ $title }}</flux:heading>
                    @isset($titleActions)
                        <span class="flex items-center">{{ $titleActions }}</span>
                    @endisset
                </div>
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

    @if (trim((string) $slot) !== '')
        <div @if ($collapsible) x-show="open" x-collapse @endif class="{{ $bodyClass }}">
            {{ $slot }}
        </div>
    @endif
</div>
@endif
