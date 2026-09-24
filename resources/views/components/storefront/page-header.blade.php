{{--
    A storefront page's title row: title + optional subtitle on the left, the
    slot (e.g. <x-storefront.checkout-steps />) on the right.
--}}
@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-sf-heading md:text-3xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-zinc-500">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
