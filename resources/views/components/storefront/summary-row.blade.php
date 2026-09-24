{{--
    One line of an order summary: label on the left, amount on the right.
    Pass `label` as a prop or a named slot (for extra markup), the value as the
    default slot. `valueClass` overrides the value styling (e.g. green "Free").
--}}
@props(['label' => null, 'valueClass' => 'font-semibold tabular-nums text-sf-heading'])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3']) }}>
    <dt class="text-zinc-500">{{ $label }}</dt>
    <dd class="{{ $valueClass }}">{{ $slot }}</dd>
</div>
