@php($selected = $this->options->filter(fn (array $option) => $this->quantityOf($option['index']) > 0))

<form wire:submit="placeOrder" id="order" class="mx-auto max-w-5xl scroll-mt-24 space-y-8 rounded-2xl bg-white p-4 shadow-xl shadow-[#1448b8]/10 ring-1 ring-zinc-100 sm:p-8">
    {{-- 1. Product --}}
    <div>
        <h2 class="text-center text-lg font-bold text-[#0b2a73]">{{ __('Choose your product') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach ($this->options as $option)
                @php($qty = $this->quantityOf($option['index']))
                <div wire:key="opt-{{ $option['index'] }}" wire:click="select({{ $option['index'] }})"
                    class="cursor-pointer rounded-lg border-2 p-3 transition {{ $qty > 0 ? 'border-[#1448b8] bg-[#1448b8]/5' : 'border-zinc-200' }} {{ $option['in_stock'] ? '' : 'opacity-60' }}">
                    <div class="flex items-center gap-3">
                        @if ($option['image'])
                            <img src="{{ $option['image'] }}" alt="" loading="lazy" class="h-12 w-12 shrink-0 rounded object-cover">
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-[#0b2a73]">{{ $option['label'] }}</p>
                            <p class="text-sm font-bold">
                                {{ format_money($option['price']) }}
                                @if ($option['discounted'])<span class="text-xs font-normal text-zinc-400 line-through">{{ format_money($option['regular']) }}</span>@endif
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-2" wire:click.stop>
                        @if ($option['in_stock'])
                            <div class="flex items-center overflow-hidden rounded border border-zinc-300 bg-white text-sm">
                                <button type="button" wire:click="decrease({{ $option['index'] }})" aria-label="{{ __('Decrease') }}" class="h-7 w-7 hover:bg-zinc-100">−</button>
                                <span class="w-8 text-center font-semibold">{{ $qty }}</span>
                                <button type="button" wire:click="increase({{ $option['index'] }})" aria-label="{{ __('Increase') }}" class="h-7 w-7 hover:bg-zinc-100">+</button>
                            </div>
                            <span class="text-sm font-bold text-[#1448b8]">{{ format_money($option['price'] * $qty) }}</span>
                        @else
                            <span class="text-xs font-semibold text-red-600">{{ __('Out of stock') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @error('quantities') <p class="mt-2 text-center text-sm font-medium text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- 2. Delivery area --}}
    @if ($this->shippingMethods->isNotEmpty())
        <fieldset>
            <legend class="w-full text-center text-lg font-bold text-[#0b2a73]">{{ __('Select delivery area') }}</legend>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($this->shippingMethods as $method)
                    <label wire:key="ship-{{ $method->id }}"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border-2 px-4 py-3 text-sm transition {{ $shipping_method_id === $method->id ? 'border-[#1448b8] bg-[#1448b8]/5' : 'border-zinc-200' }}">
                        <input type="radio" wire:model.live="shipping_method_id" value="{{ $method->id }}" class="accent-[#1448b8]">
                        <span class="flex-1 font-medium">{{ $method->name }}</span>
                        <span class="font-bold">{{ $method->cost > 0 ? format_money($method->cost) : __('Free') }}</span>
                    </label>
                @endforeach
            </div>
            @error('shipping_method_id') <span class="mt-1 block text-center text-xs font-medium text-red-600">{{ $message }}</span> @enderror
        </fieldset>
    @endif

    {{-- 3. Details + order summary --}}
    <div class="grid gap-8 md:grid-cols-2">
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-[#0b2a73]">{{ __('Delivery address') }}</h2>

            <label class="block">
                <span class="text-sm font-medium text-zinc-700">{{ __('Name') }} <span class="text-red-500">*</span></span>
                <input type="text" wire:model="customer_name" autocomplete="name" placeholder="{{ __('Your name') }}"
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2.5 focus:border-[#1448b8] focus:outline-none focus:ring-2 focus:ring-[#1448b8]/20">
                @error('customer_name') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm font-medium text-zinc-700">{{ __('Mobile number') }} <span class="text-red-500">*</span></span>
                <input type="tel" inputmode="tel" wire:model="customer_phone" autocomplete="tel" placeholder="{{ __('Enter your 11-digit mobile number') }}"
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2.5 focus:border-[#1448b8] focus:outline-none focus:ring-2 focus:ring-[#1448b8]/20">
                @error('customer_phone') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm font-medium text-zinc-700">{{ __('Full address') }} <span class="text-red-500">*</span></span>
                <textarea wire:model="shipping_address" rows="2" autocomplete="street-address" placeholder="{{ __('Write your full delivery address') }}"
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2.5 focus:border-[#1448b8] focus:outline-none focus:ring-2 focus:ring-[#1448b8]/20"></textarea>
                @error('shipping_address') <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm font-medium text-zinc-700">{{ __('Special note (if any)') }}</span>
                <textarea wire:model="notes" rows="2" placeholder="{{ __('Anything we should know?') }}"
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2.5 focus:border-[#1448b8] focus:outline-none focus:ring-2 focus:ring-[#1448b8]/20"></textarea>
            </label>
        </div>

        <div class="space-y-4">
            <h2 class="text-lg font-bold text-[#0b2a73]">{{ __('Your order') }}</h2>

            <div class="rounded-lg border border-zinc-200 text-sm">
                <div class="flex justify-between border-b border-zinc-200 px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-zinc-500">
                    <span>{{ __('Product') }}</span><span>{{ __('Subtotal') }}</span>
                </div>
                @forelse ($selected as $option)
                    <div class="flex justify-between gap-3 border-b border-zinc-100 px-4 py-3">
                        <span class="text-zinc-700">{{ $option['label'] }} <strong>× {{ $this->quantityOf($option['index']) }}</strong></span>
                        <span class="font-semibold">{{ format_money($option['price'] * $this->quantityOf($option['index'])) }}</span>
                    </div>
                @empty
                    <p class="px-4 py-3 text-zinc-400">{{ __('Pick a product first.') }}</p>
                @endforelse
                <div class="flex justify-between border-b border-zinc-100 px-4 py-3"><span class="text-zinc-600">{{ __('Subtotal') }}</span><span class="font-semibold">{{ format_money($this->subtotal) }}</span></div>
                @if ($this->vat > 0)
                    <div class="flex justify-between border-b border-zinc-100 px-4 py-3"><span class="text-zinc-600">{{ \App\Models\Setting::vatLabel() }}</span><span class="font-semibold">{{ format_money($this->vat) }}</span></div>
                @endif
                <div class="flex justify-between border-b border-zinc-100 px-4 py-3"><span class="text-zinc-600">{{ __('Shipping') }}</span><span class="font-semibold">{{ format_money($this->shippingCost) }}</span></div>
                <div class="flex justify-between px-4 py-3 text-base"><span class="font-bold">{{ __('Total') }}</span><span class="font-extrabold text-[#1448b8]">{{ format_money($this->total) }}</span></div>
            </div>

            <div class="rounded-lg bg-zinc-50 px-4 py-3 text-sm">
                <p class="font-bold text-[#0b2a73]">{{ __('Cash on delivery') }}</p>
                <p class="text-zinc-500">{{ __('Pay when the product reaches your hands.') }}</p>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1448b8] px-4 py-3.5 text-base font-bold text-white shadow-lg shadow-[#1448b8]/30 transition hover:bg-[#0f3a98] disabled:opacity-60">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                <span wire:loading.remove wire:target="placeOrder">{{ __('Complete order') }} {{ format_money($this->total) }}</span>
                <span wire:loading wire:target="placeOrder">{{ __('Placing order...') }}</span>
            </button>
        </div>
    </div>
</form>
