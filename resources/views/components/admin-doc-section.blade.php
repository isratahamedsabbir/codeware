@props([
    'id',
    'icon' => 'book-open',
    'title',
    'description' => null,
    'number' => null,
])

@php
    $anchor = 'doc-'.$id;

    // Titles are written "3. Features", so the badge can be lifted straight off
    // the heading rather than passed in separately - one source for the number
    // that cannot drift from the number the reader sees.
    $number ??= preg_match('/^\s*(\d+)\./', $title, $m) ? $m[1] : null;
@endphp

<section id="{{ $anchor }}" data-doc-section
    class="doc-section scroll-mt-24 overflow-hidden rounded-[5px] border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800/40">

    <div class="flex items-start gap-3.5 border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
            <x-dynamic-component :component="'flux::icon.'.$icon" class="size-5" />
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                @if ($number)
                    <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300">
                        {{ $number }}
                    </span>
                @endif
                <flux:heading size="sm">{{ $title }}</flux:heading>
            </div>

            @if ($description)
                <flux:text class="mt-0.5 font-mono text-[11px] text-zinc-400">{{ $description }}</flux:text>
            @endif
        </div>

        <a href="#{{ $anchor }}"
            class="doc-section-anchor -mr-1 mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
            aria-label="Link to this section">
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
            </svg>
        </a>
    </div>

    <div class="space-y-5 px-6 py-5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
        {{ $slot }}
    </div>
</section>