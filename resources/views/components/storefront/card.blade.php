{{--
    The storefront's white card with an optional tinted header — used by the
    cart, checkout, order confirmation and account order pages.

    Header: `title` (+ `subtitle`); `step` adds a numbered circle (the checkout
    form's 1-2-3 sections); the `actions` slot sits on the right (a count
    badge, an "Edit cart" link…). The body is the default slot, unpadded, so
    each card lays out its own content. Pass `as="aside"` etc. for the tag.
--}}
@props(['title' => null, 'subtitle' => null, 'step' => null, 'as' => 'section'])

<{{ $as }} {{ $attributes->merge(['class' => 'overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm']) }}>
    @if ($title)
        <header class="flex items-center justify-between gap-3 border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
            <div class="flex min-w-0 items-center gap-3">
                @if ($step)
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white shadow-sm">{{ $step }}</span>
                @endif
                <div class="min-w-0">
                    <h2 @class([
                        'font-bold text-sf-heading',
                        'text-sm uppercase tracking-wide' => $step,
                        'text-base' => ! $step,
                    ])>{{ $title }}</h2>
                    @if ($subtitle)
                        <p class="text-xs text-zinc-500">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    {{ $slot }}
</{{ $as }}>
