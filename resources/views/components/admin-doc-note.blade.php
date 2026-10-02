@props([
    // The four tones a technical page actually needs: something to copy, a
    // trap, a rule that is load-bearing, and a thing worth knowing.
    'tone' => 'info',
    'title' => null,
])

@php
    [$frame, $chip, $body] = match ($tone) {
        'tip' => ['border-emerald-200 bg-emerald-50/70 dark:border-emerald-500/30 dark:bg-emerald-500/10', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400', 'text-emerald-800 dark:text-emerald-200'],
        'warning' => ['border-amber-200 bg-amber-50/70 dark:border-amber-500/30 dark:bg-amber-500/10', 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400', 'text-amber-800 dark:text-amber-200'],
        'danger' => ['border-rose-200 bg-rose-50/70 dark:border-rose-500/30 dark:bg-rose-500/10', 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400', 'text-rose-800 dark:text-rose-200'],
        default => ['border-blue-200 bg-blue-50/70 dark:border-blue-500/30 dark:bg-blue-500/10', 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400', 'text-blue-800 dark:text-blue-200'],
    };

    $icon = match ($tone) {
        'tip' => 'light-bulb',
        'warning' => 'exclamation-triangle',
        'danger' => 'exclamation-circle',
        default => 'information-circle',
    };
@endphp

<div class="flex items-start gap-3 rounded-lg border p-4 text-sm leading-relaxed {{ $frame }}">
    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $chip }}">
        <x-dynamic-component :component="'flux::icon.'.$icon" class="size-4" />
    </span>
    <div class="min-w-0 flex-1 {{ $body }}">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-1 text-xs leading-relaxed opacity-90' : 'text-xs leading-relaxed opacity-90' }}">
            {{ $slot }}
        </div>
    </div>
</div>