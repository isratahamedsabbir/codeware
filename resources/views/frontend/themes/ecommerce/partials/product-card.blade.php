@php
    $isUpcoming = $product->is_upcoming;
    $inStock = $product->inStock();
    $discount = $product->hasDiscount() ? (float) $product->discount_price : null;
    $discountPercent = $discount && (float) $product->price > 0
        ? round((1 - $discount / (float) $product->price) * 100)
        : null;
@endphp

<div class="group relative flex flex-col overflow-hidden rounded-card bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('products.show', $product->slug) }}" class="relative block overflow-hidden bg-zinc-100" aria-label="{{ $product->name }}">
        @if ($product->featured_image)
            <img src="{{ $product->featured_image }}" alt="{{ $product->name }}"
                loading="lazy"
                class="h-[190px] w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-[190px] w-full items-center justify-center text-zinc-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                </svg>
            </div>
        @endif

        @if ($discountPercent)
            <span class="absolute left-2.5 top-2.5 rounded bg-sale px-2 py-0.5 text-xs font-bold text-white">
                -{{ $discountPercent }}%
            </span>
        @elseif ($isUpcoming)
            <span class="absolute left-2.5 top-2.5 rounded bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">
                {{ __('Upcoming') }}
            </span>
        @endif

        @if (! $inStock && ! $isUpcoming)
            <span class="absolute bottom-2.5 left-2.5 rounded bg-zinc-900/80 px-2 py-0.5 text-xs font-semibold text-white">
                {{ __('Out of stock') }}
            </span>
        @endif
    </a>

    <livewire:frontend.wishlist-button
        :product-id="$product->id"
        :key="'wishlist-'.$product->id"
    />

    <div class="flex flex-1 flex-col gap-1 p-3">
        @if ($product->brand)
            <span class="text-xs uppercase tracking-wide text-zinc-400">{{ $product->brand->name }}</span>
        @endif

        <h3 class="text-[15px] font-medium leading-snug text-sf-text">
            <a href="{{ route('products.show', $product->slug) }}" class="line-clamp-2 min-h-[36px] transition-colors hover:text-brand">
                {{ $product->name }}
            </a>
        </h3>

        <div class="mt-auto flex flex-wrap items-baseline gap-x-2 gap-y-0.5 pt-2">
            @if ($discount)
                <span class="text-[15px] font-bold text-sf-price">{{ format_money($discount) }}</span>
                <span class="text-sm text-gray-400 line-through">{{ format_money($product->price) }}</span>
            @else
                <span class="text-[15px] font-bold text-sf-price">{{ format_money($product->price) }}</span>
            @endif

            @if (($sold = $product->soldQuantity()) > 0)
                <span class="ms-auto inline-flex items-center gap-1 text-xs text-zinc-400" title="{{ __('Units sold') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5">
                        <path d="M2.25 2.25a.75.75 0 0 0 0 1.5h1.386c.17 0 .318.11.37.276l2.11 7.385a2.25 2.25 0 0 0 2.15 1.639h6.807c.928 0 1.755-.57 2.095-1.429l1.928-4.82a.75.75 0 0 0-1.394-.558l-1.928 4.82a.75.75 0 0 1-.701.482H8.295l-.241-.842a.75.75 0 0 1-.075-.285l-1.667-5.83a1.5 1.5 0 0 0-1.443-1.09H2.25Z" />
                        <path d="M11 19.5a1.5 1.5 0 1 0 3 0 1.5 1.5 0 0 0-3 0Zm-5 0a1.5 1.5 0 1 0 3 0 1.5 1.5 0 0 0-3 0Z" />
                    </svg>
                    {{ $sold }} {{ __('sold') }}
                </span>
            @endif
        </div>

        <div class="pt-3">
            <livewire:frontend.add-to-cart-button
                :product-id="$product->id"
                :slug="$product->slug"
                :in-stock="$inStock"
                :is-upcoming="$isUpcoming"
                :requires-options="$product->hasVisibleVariations()"
                :has-variations="$product->hasVisibleVariations()"
                :key="'add-to-cart-'.$product->id"
            />
        </div>
    </div>
</div>