@props(['text'])

<button type="button" x-data="{ copied: false }"
    x-on:click.stop="
        navigator.clipboard.writeText(@js((string) $text));
        copied = true;
        setTimeout(() => copied = false, 1200);
    "
    {{ $attributes->class(['relative inline-flex items-center gap-1 text-left cursor-pointer hover:text-indigo-600 transition-colors min-w-0']) }}>
    <span class="truncate">{{ $slot }}</span>
    <span x-cloak x-show="copied" x-transition.opacity
        class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-1 rounded text-[11px] font-medium bg-zinc-800 text-white whitespace-nowrap z-10">
        Copied!
        <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-800"></span>
    </span>
</button>
