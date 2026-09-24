{{-- The highlighted "Total" line at the bottom of an order summary. --}}
@props(['label' => null])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between rounded-card bg-brand/5 px-3.5 py-3']) }}>
    <dt class="text-base font-bold text-sf-heading">{{ $label ?? __('Total') }}</dt>
    <dd class="text-xl font-extrabold tabular-nums text-sf-price">{{ $slot }}</dd>
</div>
