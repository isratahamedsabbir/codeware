@props(['text' => null])

@if ($text)
    <flux:tooltip toggleable class="inline-flex align-middle h-3.5">
        <button type="button" tabindex="-1"
            class="inline-flex items-center justify-center h-3.5 text-zinc-400 hover:text-zinc-600 cursor-pointer ml-1"
            aria-label="More info">
            <flux:icon.information-circle class="size-3.5" />
        </button>
        <flux:tooltip.content class="max-w-64">{!! $text !!}</flux:tooltip.content>
    </flux:tooltip>
@endif
