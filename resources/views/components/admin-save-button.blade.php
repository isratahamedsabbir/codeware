@props([
    'label',
    'savingLabel' => 'Saving...',
    'method' => 'save',
])

<button wire:click="{{ $method }}" wire:loading.attr="disabled" wire:target="{{ $method }}"
    {{ $attributes->class(['admin-btn-save inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors']) }}>
    <svg wire:loading.remove wire:target="{{ $method }}" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
        <polyline points="17 21 17 13 7 13 7 21" />
        <polyline points="7 3 7 8 15 8" />
    </svg>
    <svg wire:loading wire:target="{{ $method }}" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="9" stroke-opacity="0.25" />
        <path d="M21 12a9 9 0 0 0-9-9" stroke-opacity="1" />
    </svg>
    <span wire:loading.remove wire:target="{{ $method }}">{{ $label }}</span>
    <span wire:loading wire:target="{{ $method }}">{{ $savingLabel }}</span>
</button>
