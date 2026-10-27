<form wire:submit="placeOrder" id="order" class="mx-auto grid max-w-5xl scroll-mt-24 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-zinc-200 lg:grid-cols-[1fr_26rem]">
    {{-- Customer details + delivery area --}}
    <div class="order-2 space-y-4 p-4 sm:p-5 lg:order-1">
        <h2 class="text-lg font-bold text-sf-heading">{{ __('Fill in these details to place your order') }}</h2>

        <label class="block">
            <span class="text-xs font-medium text-zinc-600">{{ __('Name') }}</span>
            <input type="text" wire:model="customer_name" autocomplete="name" placeholder="{{ __('Your name') }}"
                class="mt-1 w-full rounded border border-zinc-300 px-3 py-2.5 text-base focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            @error('customer_name') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-xs font-medium text-zinc-600">{{ __('Mobile number') }}</span>
            <input type="tel" inputmode="tel" wire:model="customer_phone" autocomplete="tel" placeholder="{{ __('Enter your 11-digit mobile number') }}"
                class="mt-1 w-full rounded border border-zinc-300 px-3 py-2.5 text-base focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            @error('customer_phone') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-xs font-medium text-zinc-600">{{ __('Delivery address') }}</span>
            <textarea wire:model="shipping_address" rows="2" autocomplete="street-address" placeholder="{{ __('Write your full delivery address') }}"
                class="mt-1 w-full rounded border border-zinc-300 px-3 py-2.5 text-base focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20"></textarea>
            @error('shipping_address') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-xs font-medium text-zinc-600">{{ __('Additional note') }}</span>
            <input type="text" wire:model="notes" placeholder="{{ __('Anything we should know? (optional)') }}"
                class="mt-1 w-full rounded border border-zinc-300 px-3 py-2.5 text-base focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
        </label>

        @if ($this->shippingMethods->isNotEmpty())
            <fieldset>
                <legend class="text-xs font-medium text-zinc-600">{{ __('Delivery area') }}</legend>
                <div class="mt-1.5 space-y-2">
                    @foreach ($this->shippingMethods as $method)
                        <label wire:key="ship-{{ $method->id }}"
                            class="flex cursor-pointer items-center gap-3 rounded border px-3 py-2.5 text-sm transition {{ $shipping_method_id === $method->id ? 'border-brand bg-brand/5' : 'border-zinc-300 bg-white' }}">
                            <input type="radio" wire:model.live="shipping_method_id" value="{{ $method->id }}" class="accent-brand">
                            <span class="flex-1">{{ $method->name }}</span>
                            <span class="font-semibold">{{ $method->cost > 0 ? format_money($method->cost) : __('Free') }}</span>
                        </label>
                    @endforeach
                </div>
                @error('shipping_method_id') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </fieldset>
        @endif

        <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
            class="w-full rounded bg-linear-to-r from-orange-500 to-orange-600 px-4 py-3.5 text-base font-bold text-white shadow-md transition hover:from-orange-600 hover:to-orange-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="placeOrder">{{ __('Confirm order') }} {{ format_money($this->total) }}</span>
            <span wire:loading wire:target="placeOrder">{{ __('Placing order...') }}</span>
        </button>
        <p class="text-center text-xs text-zinc-500">{{ __('Pay the total bill, including product price and delivery charge, to the courier when you receive the product.') }}</p>
    </div>

    {{-- Product choice + totals. On a phone this comes first: pick, then fill in. --}}
    <div class="order-1 space-y-3 border-b border-zinc-200 bg-zinc-50 p-4 sm:p-5 lg:order-2 lg:border-b-0 lg:border-l">
        <h2 class="text-lg font-bold text-sf-heading">{{ __('Choose your product') }}</h2>

        @foreach ($this->options as $option)
            @php($qty = $this->quantityOf($option['index']))
            <div wire:key="opt-{{ $option['index'] }}" wire:click="select({{ $option['index'] }})"
                class="relative flex cursor-pointer gap-3 rounded-lg border-2 bg-white p-3 transition {{ $qty > 0 ? 'border-brand shadow-sm' : 'border-zinc-200' }} {{ $option['in_stock'] ? '' : 'opacity-60' }}">
                <span class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border {{ $qty > 0 ? 'border-brand' : 'border-zinc-400' }}">
                    @if ($qty > 0) <span class="h-2 w-2 rounded-full bg-brand"></span> @endif
                </span>
                @if ($option['image'])
                    <img src="{{ $option['image'] }}" alt="" loading="lazy" class="h-16 w-16 shrink-0 rounded object-cover">
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-sf-heading">{{ $option['label'] }}</p>
                    <p class="mt-0.5 flex items-baseline gap-2">
                        <span class="text-lg font-extrabold text-sf-heading">{{ format_money($option['price']) }}</span>
                        @if ($option['discounted'])
                            <span class="text-xs text-zinc-400 line-through">{{ format_money($option['regular']) }}</span>
                        @endif
                    </p>
                    @if (! $option['in_stock'])
                        <p class="text-xs font-semibold text-red-600">{{ __('Out of stock') }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 self-end text-sm" wire:click.stop>
                    <span class="hidden text-zinc-500 sm:inline">{{ __('Quantity') }}:</span>
                    <div class="flex items-center overflow-hidden rounded border border-zinc-300 bg-white">
                        <button type="button" wire:click="decrease({{ $option['index'] }})" aria-label="{{ __('Decrease') }}" class="h-8 w-8 hover:bg-zinc-100">−</button>
                        <span class="w-9 text-center font-semibold">{{ $qty }}</span>
                        <button type="button" wire:click="increase({{ $option['index'] }})" aria-label="{{ __('Increase') }}" class="h-8 w-8 hover:bg-zinc-100">+</button>
                    </div>
                </div>
            </div>
        @endforeach
        @error('quantities') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror

        <dl class="space-y-2.5 rounded-lg border border-zinc-200 bg-white p-4 text-sm">
            <div class="flex justify-between"><dt class="text-zinc-600">{{ __('Sub-Total') }}</dt><dd class="font-bold">{{ format_money($this->subtotal) }}</dd></div>
            @if ($this->vat > 0)
                <div class="flex justify-between"><dt class="text-zinc-600">{{ \App\Models\Setting::vatLabel() }}</dt><dd class="font-bold">{{ format_money($this->vat) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-zinc-600">{{ __('Delivery charge') }}</dt><dd class="font-bold">{{ format_money($this->shippingCost) }}</dd></div>
            <div class="flex items-baseline justify-between border-t border-zinc-200 pt-3"><dt class="font-bold">{{ __('Total') }}</dt><dd class="text-2xl font-extrabold text-brand">{{ format_money($this->total) }}</dd></div>
        </dl>
    </div>
</form>
