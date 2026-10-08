@php
    // format_money() returns "৳ 1,055.81" as plain text (it feeds admin and mail too), so the
    // symbol is split off here and enlarged — the taka glyph renders tiny next to the digits.
    $money = function ($amount): \Illuminate\Support\HtmlString {
        $text = format_money($amount);
        $cut = strrpos($text, ' ');

        return new \Illuminate\Support\HtmlString(
            $cut === false
                ? e($text)
                : '<span class="text-[1.5em] font-black leading-none">'.e(substr($text, 0, $cut)).'</span> '.e(substr($text, $cut + 1))
        );
    };

    $isUpcoming = $product->is_upcoming;
    $inStock = $product->inStock();
    $discount = $product->effectiveDiscount();
    $discountPercent = $discount && (float) $product->price > 0
        ? round((1 - $discount / (float) $product->price) * 100)
        : null;

    // Every option the visible combinations offer, grouped by attribute
    // (Size => [250g, 500g], Color => [...]) so the card can hint at the choices.
    // A value counts as available when at least one combination carrying it has stock
    // (blank quantity = out of stock, same rule as Product::variationInStock()).
    $variantOptions = [];
    foreach ($product->visibleVariations() as $row) {
        $rowInStock = (int) ($row['quantity'] ?? 0) > 0;
        foreach (($row['attributes'] ?? []) as $name => $value) {
            if (filled($value)) {
                $variantOptions[$name][$value] = ($variantOptions[$name][$value] ?? false) || $rowInStock;
            }
        }
    }
    $variantOptions = collect($variantOptions)->take(2);
@endphp

<div class="group relative flex flex-col overflow-hidden rounded-card bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('products.show', $product->slug) }}" class="relative block overflow-hidden bg-zinc-100" aria-label="{{ $product->name }}">
        @if ($product->featured_image)
            <img src="{{ $product->featured_image }}" alt="{{ $product->name }}"
                loading="lazy" decoding="async" width="380" height="190"
                class="h-[190px] w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-[190px] w-full items-center justify-center text-zinc-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                </svg>
            </div>
        @endif

        @if ($discountPercent)
            <span class="absolute left-2.5 top-2.5 rounded-md bg-sale px-3 py-1.5 text-lg font-bold leading-none text-white">
                -{{ $discountPercent }}%
            </span>
        @elseif ($isUpcoming)
            <span class="absolute left-2.5 top-2.5 rounded-md bg-amber-500 px-3 py-1.5 text-lg font-bold leading-none text-white">
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
            <span class="text-xs uppercase tracking-wide text-zinc-500">{{ $product->brand->name }}</span>
        @endif

        <h3 class="text-[15px] font-medium leading-snug text-sf-text">
            <a href="{{ route('products.show', $product->slug) }}" class="line-clamp-2 min-h-[36px] transition-colors hover:text-brand">
                {{ $product->name }}
            </a>
        </h3>

        @if ($variantOptions->isNotEmpty())
            <div class="mt-1.5 flex flex-col gap-1.5">
                @foreach ($variantOptions as $attributeName => $values)
                    <div class="flex flex-wrap items-center gap-1.5" title="{{ $attributeName }}">
                        @foreach (array_slice($values, 0, 4, true) as $value => $available)
                            @if ($available)
                                <a href="{{ route('products.show', $product->slug) }}"
                                    class="rounded-md border border-zinc-300 px-2.5 py-1 text-[13px] font-medium leading-none text-zinc-700 transition hover:border-brand hover:text-brand">{{ $value }}</a>
                            @else
                                <span class="cursor-not-allowed rounded-md border border-zinc-200 px-2.5 py-1 text-[13px] font-medium leading-none text-zinc-400 line-through opacity-70" title="{{ __('Out of stock') }}">{{ $value }}</span>
                            @endif
                        @endforeach
                        @if (count($values) > 4)
                            <span class="text-[13px] font-medium text-zinc-500">+{{ count($values) - 4 }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-auto flex flex-wrap items-baseline gap-x-2 gap-y-0.5 pt-2">
            @if ($discount)
                <span class="text-[15px] font-bold text-sf-price">{{ $money($discount) }}</span>
                <span class="text-sm text-sale line-through">{{ $money($product->price) }}</span>
            @else
                <span class="text-[15px] font-bold text-sf-price">{{ $money($product->price) }}</span>
            @endif

            @if (($sold = $product->soldQuantity()) > 0)
                <span class="ms-auto inline-flex items-center gap-1 text-xs text-zinc-500" title="{{ __('Units sold') }}">
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