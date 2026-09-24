{{-- "3 items" pill for card headers. --}}
@props(['count'])

<span {{ $attributes->merge(['class' => 'rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-bold text-brand']) }}>
    {{ trans_choice(':count item|:count items', $count, ['count' => $count]) }}
</span>
