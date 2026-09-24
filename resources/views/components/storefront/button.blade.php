{{--
    The storefront's primary call-to-action (Proceed to checkout, Place order…).
    Renders an <a> when given `href`, otherwise a <button> — both with the same
    5px corners (the global `button` rule would force those on a <button>
    anyway, so the link matches it exactly).

    `icon` is a <x-storefront.icon> name shown before the label. `loading`
    names a Livewire action: while it runs the icon becomes a spinner and the
    label becomes `loadingText`.
--}}
@props(['href' => null, 'icon' => null, 'loading' => null, 'loadingText' => null])

@php
    $classes = 'flex w-full items-center justify-center gap-2 rounded-[5px] bg-sf-button px-6 py-4 text-sm font-bold text-sf-button-text shadow-md shadow-brand/20 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60';
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $attributes->get('type', 'submit') }}" @endif
    @if ($loading) wire:loading.attr="disabled" wire:target="{{ $loading }}" @endif
    {{ $attributes->except('type')->merge(['class' => $classes]) }}>
    @if ($loading)
        <svg wire:loading wire:target="{{ $loading }}" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
        </svg>
    @endif

    @if ($icon && $loading)
        <span wire:loading.remove wire:target="{{ $loading }}" class="contents">
            <x-storefront.icon :name="$icon" class="h-4.5 w-4.5" />
        </span>
    @elseif ($icon)
        <x-storefront.icon :name="$icon" class="h-4.5 w-4.5" />
    @endif

    @if ($loading && $loadingText)
        <span wire:loading.remove wire:target="{{ $loading }}">{{ $slot }}</span>
        <span wire:loading wire:target="{{ $loading }}">{{ $loadingText }}</span>
    @else
        <span>{{ $slot }}</span>
    @endif
</{{ $tag }}>
