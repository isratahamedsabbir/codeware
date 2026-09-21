<div
    x-data="{ open: false }"
    class="relative w-full"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <form action="{{ route('shop') }}" method="GET" class="relative">
        <input
            type="text"
            name="search"
            value="{{ $query }}"
            wire:model.live.debounce.400ms="query"
            @focus="open = true"
            placeholder="{{ __('Search products...') }}"
            autocomplete="off"
            aria-label="{{ __('Search products') }}"
            class="{{ $onDark
                ? 'w-full rounded-full bg-white py-2.5 pl-5 pr-11 text-sm text-gray-700 placeholder-gray-400 outline-none focus:ring-2 focus:ring-white/60'
                : 'w-full rounded-full border border-zinc-200 bg-gray-50 py-2.5 pl-5 pr-11 text-sm outline-none focus:border-brand' }}"
        >
        <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-brand" aria-label="{{ __('Search') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
            </svg>
        </button>
    </form>

    @if ($suggestions !== [])
        <div x-show="open" x-transition
            class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-md border border-zinc-100 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.12)]">
            <ul class="max-h-80 overflow-y-auto py-1">
                @foreach ($suggestions as $suggestion)
                    <li>
                        <a href="{{ route('products.show', $suggestion['slug']) }}"
                            @click="open = false"
                            class="flex items-center gap-3 px-3 py-2 transition-colors hover:bg-gray-50">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded bg-zinc-100">
                                @if ($suggestion['image'])
                                    <img src="{{ $suggestion['image'] }}" alt="{{ $suggestion['name'] }}" class="h-full w-full object-cover">
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                                    </svg>
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-zinc-800">{{ $suggestion['name'] }}</span>
                                <span class="flex items-baseline gap-1.5">
                                    <span class="text-sm font-bold text-brand">{{ $suggestion['price'] }}</span>
                                    @if ($suggestion['old_price'])
                                        <span class="text-xs text-gray-400 line-through">{{ $suggestion['old_price'] }}</span>
                                    @endif
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('shop', ['search' => $query]) }}"
                @click="open = false"
                class="block border-t border-zinc-100 px-3 py-2 text-center text-sm font-semibold text-brand transition-colors hover:bg-gray-50">
                {{ __('View all results') }}
            </a>
        </div>
    @endif
</div>