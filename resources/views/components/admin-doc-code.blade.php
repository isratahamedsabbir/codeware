@props([
    'label' => null,
    'file' => null,
    // Drives the highlighter. Guessed from the filename when it is not given,
    // so the 20-odd call sites in the guide do not each have to declare it.
    'lang' => null,
])

@php
    use App\Support\DocHighlight;

    $ext = strtolower(pathinfo($file ?? '', PATHINFO_EXTENSION));

    $lang ??= match ($ext) {
        'json' => 'json',
        'txt', 'log' => 'text',
        default => 'php',
    };

    // The slot arrives as a ComponentSlot of already-escaped markup: the guide
    // writes its angle brackets as &lt; so Blade shows them instead of
    // compiling them, which means the text has to be decoded back to source
    // before tokenizing and re-escaped per token afterwards. Decoding here
    // rather than in DocHighlight keeps that a property of how Blade hands
    // slots over rather than of the highlighter's contract.
    $source = html_entity_decode(trim((string) $slot), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $highlights = DocHighlight::render($source, $lang);
@endphp

{{-- x-data is only here for the copy button's transient "Copied" state. --}}
<div x-data="{ copied: false }"
    class="doc-code group overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/40">

    <div class="flex items-center gap-3 border-b border-zinc-200 bg-white/70 px-3 py-1.5 dark:border-zinc-700 dark:bg-zinc-800/40">
        {{-- Three dots, the way every code block in every tool reads. --}}
        <span class="flex shrink-0 items-center gap-1" aria-hidden="true">
            <span class="size-2 rounded-full bg-rose-400"></span>
            <span class="size-2 rounded-full bg-amber-400"></span>
            <span class="size-2 rounded-full bg-emerald-400"></span>
        </span>

        <span class="min-w-0 flex-1 truncate text-[11px] font-semibold text-zinc-600 dark:text-zinc-300">
            {{ $label }}
            @if ($file)
                <span class="ml-1.5 font-mono font-normal text-zinc-400">{{ $file }}</span>
            @endif
        </span>

        <button type="button"
            x-on:click="navigator.clipboard.writeText($refs.source.textContent); copied = true; setTimeout(() => copied = false, 1600)"
            x-bind:aria-label="copied ? 'Copied' : 'Copy code'"
            class="inline-flex shrink-0 items-center gap-1 rounded px-1.5 py-0.5 text-[11px] font-medium text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 dark:hover:bg-zinc-700 dark:hover:text-zinc-100">
            <span x-show="! copied" class="flex items-center gap-1">
                <flux:icon.clipboard class="size-3.5" aria-hidden="true" />
                Copy
            </span>
            <span x-show="copied" x-cloak class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                <flux:icon.check class="size-3.5" aria-hidden="true" />
                Copied
            </span>
        </button>
    </div>

    {{-- x-ref="source" doubles as the copy target: every token in $highlights is
         escaped and wrapped in a span, so this element's textContent is the
         original source exactly, and no hidden duplicate of the snippet is
         needed to hold the plain text. --}}
    <div class="overflow-x-auto">
        <pre class="px-4 py-3 font-mono text-xs leading-relaxed"><code x-ref="source">{!! $highlights !!}</code></pre>
    </div>
</div>