{{--
    Centered "nothing here yet" card with an icon, a message and an optional
    call to action (e.g. an empty cart → Browse products).
--}}
@props(['icon' => 'cart', 'title', 'text' => null, 'actionHref' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'mx-auto flex max-w-md flex-col items-center rounded-card border border-dashed border-zinc-300 bg-white px-6 py-16 text-center shadow-sm']) }}>
    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand/10 text-brand">
        <x-storefront.icon :name="$icon" stroke="1.5" class="h-8 w-8" />
    </span>
    <h2 class="mt-5 text-lg font-bold text-sf-heading">{{ $title }}</h2>
    @if ($text)
        <p class="mt-1 text-sm text-zinc-500">{{ $text }}</p>
    @endif
    @if ($actionHref && $actionLabel)
        <a href="{{ $actionHref }}"
            class="mt-6 inline-flex items-center gap-2 rounded-[5px] bg-sf-button px-6 py-2.5 text-sm font-semibold text-sf-button-text shadow-sm transition hover:opacity-90">
            {{ $actionLabel }}
            <x-storefront.icon name="arrow-right" />
        </a>
    @endif
</div>
