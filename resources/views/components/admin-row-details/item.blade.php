@props(['label'])

<div class="flex items-center justify-between gap-4 py-2 text-sm">
    <span class="text-zinc-400">{{ $label }}</span>
    <span class="text-zinc-900 text-right">{{ $slot }}</span>
</div>
