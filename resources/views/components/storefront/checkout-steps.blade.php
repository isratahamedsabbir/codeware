{{--
    The purchase-flow tracker: Cart → Checkout → Order placed. `current` is the
    step the shopper is on (1–3); steps before it show as done (the cart step
    links back to the cart), later ones as upcoming. current > 3 = all done.
--}}
@props(['current' => 1])

@php
    $steps = [
        1 => ['label' => __('Cart'), 'href' => route('cart')],
        2 => ['label' => __('Checkout'), 'href' => null],
        3 => ['label' => __('Order placed'), 'href' => null],
    ];
@endphp

<ol {{ $attributes->merge(['class' => 'flex items-center gap-2 text-xs font-semibold sm:gap-3']) }}>
    @foreach ($steps as $number => $step)
        @php
            $state = $number < $current ? 'done' : ($number === $current ? 'current' : 'upcoming');
            $linked = $state === 'done' && $step['href'];
        @endphp
        <li class="flex items-center gap-2">
            @if ($linked) <a href="{{ $step['href'] }}" class="flex items-center gap-2 transition hover:text-brand"> @endif
                <span @class([
                    'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                    'bg-emerald-500 text-white shadow-sm' => $state === 'done',
                    'bg-brand text-white shadow-sm' => $state === 'current',
                    'border border-zinc-300 bg-white text-zinc-400' => $state === 'upcoming',
                ])>
                    @if ($state === 'done')
                        <x-storefront.icon name="check" stroke="3" class="h-3.5 w-3.5" />
                    @else
                        {{ $number }}
                    @endif
                </span>
                <span @class(['text-zinc-400' => $state === 'upcoming', 'text-sf-heading' => $state !== 'upcoming'])>{{ $step['label'] }}</span>
            @if ($linked) </a> @endif

            @unless ($loop->last)
                <span @class(['h-px w-6 sm:w-10', 'bg-emerald-400' => $state === 'done', 'bg-zinc-300' => $state !== 'done'])></span>
            @endunless
        </li>
    @endforeach
</ol>
