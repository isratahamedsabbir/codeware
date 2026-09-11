@props(['expanded' => false])

<button type="button"
    aria-label="{{ $expanded ? 'Collapse' : 'Expand' }}"
    aria-expanded="{{ $expanded ? 'true' : 'false' }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center w-6 h-6 rounded-full border transition-colors cursor-pointer shrink-0',
        'bg-indigo-500 border-indigo-500 text-white hover:bg-indigo-600' => $expanded,
        'border-zinc-300 text-zinc-400 hover:border-zinc-400 hover:text-zinc-600' => ! $expanded,
    ]) }}>
    @if ($expanded)
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
    @else
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
    @endif
</button>
