<div class="max-w-3xl w-full mx-auto space-y-4">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('delivery.orders') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    {{-- Customer & address --}}
    <div class="admin-card p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs text-zinc-500">Order</p>
                <p class="font-mono text-lg font-semibold text-zinc-900">{{ $order->order_number }}</p>
            </div>
            @include('livewire.vendor.orders.partials.status-badge', ['status' => $order->status])
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5 pt-5 border-t border-zinc-100">
            <div>
                <p class="text-xs text-zinc-500 mb-1">Customer</p>
                <p class="text-sm font-medium text-zinc-800">{{ $order->customer_name }}</p>
                <a href="tel:{{ $order->customer_phone }}" class="text-sm text-indigo-600 hover:underline">{{ $order->customer_phone }}</a>
            </div>
            <div>
                <p class="text-xs text-zinc-500 mb-1">Shipping Address</p>
                <p class="text-sm text-zinc-800 whitespace-pre-line">{{ $order->shipping_address }}</p>
            </div>
        </div>

        @if ($order->notes)
            <div class="mt-4 pt-4 border-t border-zinc-100">
                <p class="text-xs text-zinc-500 mb-1">Notes</p>
                <p class="text-sm text-zinc-700">{{ $order->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Items --}}
    <div class="admin-card overflow-hidden">
        <div class="px-5 py-3 border-b border-zinc-100">
            <flux:heading size="sm">Items</flux:heading>
        </div>
        <ul class="divide-y divide-zinc-100">
            @foreach ($order->items as $item)
                <li class="px-5 py-3 flex items-center justify-between gap-4 text-sm">
                    <span class="text-zinc-800">{{ $item->item_name }}</span>
                    <span class="text-zinc-500 shrink-0">× {{ $item->quantity }}</span>
                </li>
            @endforeach
        </ul>
        <div class="px-5 py-3 border-t border-zinc-100 flex items-center justify-between text-sm">
            <span class="text-zinc-500">
                {{ \App\Support\PaymentMethods::label($order->payment_method) }} · {{ ucfirst($order->payment_status) }}
            </span>
            <span class="font-semibold text-zinc-900">{{ $order->currency }} {{ number_format((float) $order->total, 2) }}</span>
        </div>
    </div>

    {{-- Delivery confirmation --}}
    <div class="admin-card p-5">
        <flux:heading size="sm" class="mb-1">Complete Delivery</flux:heading>

        @if ($order->status === 'delivered')
            <div class="flex items-center gap-2 text-sm text-emerald-700">
                <flux:icon.check-circle class="size-5" />
                Delivered {{ $order->delivered_at?->toDisplay() }}
            </div>
        @elseif ($order->status === 'cancelled')
            <p class="text-sm text-rose-600">This order was cancelled — do not deliver it.</p>
        @elseif (! $otpSent)
            <p class="text-sm text-zinc-500 mb-4">
                When you hand over the order, send a code to the customer's email. The customer tells you the code and you enter it here.
            </p>
            <flux:button variant="primary" icon="envelope" wire:click="sendOtp" wire:loading.attr="disabled" wire:target="sendOtp" class="w-full sm:w-auto">
                <span wire:loading.remove wire:target="sendOtp">Send Code to Customer</span>
                <span wire:loading wire:target="sendOtp">Sending…</span>
            </flux:button>
            <flux:error name="otp" class="mt-2" />
        @else
            <p class="text-sm text-zinc-500 mb-4">
                A 6-digit code was emailed to the customer. Ask them for it and enter it below.
            </p>
            <form wire:submit="confirmDelivery" class="space-y-3">
                <input wire:model="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••"
                    class="w-full sm:w-56 text-center text-2xl tracking-[0.5em] font-mono px-3 py-2.5 border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" />
                <flux:error name="otp" />

                <div class="flex items-center gap-2 flex-wrap">
                    <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="confirmDelivery">
                        Confirm Delivered
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="sendOtp" wire:loading.attr="disabled" wire:target="sendOtp">
                        Resend Code
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
</div>
