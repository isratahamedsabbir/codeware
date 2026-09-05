@props([
    'icon' => 'squares-2x2',
    'title',
    'description' => null,
    'iconColor' => 'bg-primary/10 text-primary',
    'actions' => null,
    'bodyClass' => 'px-6 py-5 space-y-4',
    'headerBorder' => 'border-zinc-200',
])

<div {{ $attributes->class(['rounded-[5px] bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/40 overflow-hidden']) }}>
    <div class="flex items-center justify-between gap-3 px-6 py-4 border-b {{ $headerBorder }} dark:border-zinc-700">
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
        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </div>

    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
