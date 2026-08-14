<div class="max-w-[1200px] w-full mx-auto">

    <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.orders') }}" wire:navigate class="mb-4 border-2">
        Back to Orders
    </flux:button>

    <div class="flex gap-5 items-start flex-wrap lg:flex-nowrap">

        {{-- Main --}}
        <div class="flex-1 min-w-0 space-y-5">

            {{-- Customer & shipping --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm p-6">
                <flux:heading size="lg" class="mb-1">Order {{ $order->order_number }}</flux:heading>
                <p class="text-sm text-zinc-500 mb-5">Placed {{ $order->created_at->format('M d, Y g:i A') }}</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Customer</h3>
                        <p class="text-sm text-zinc-800">{{ $order->customer_name }}</p>
                        <p class="text-sm text-zinc-500">{{ $order->customer_email }}</p>
                        <p class="text-sm text-zinc-500">{{ $order->customer_phone }}</p>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Shipping Address</h3>
                        <p class="text-sm text-zinc-800 whitespace-pre-line">{{ $order->shipping_address }}</p>
                    </div>
                </div>

                @if ($order->notes)
                    <div class="mt-5">
                        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Notes</h3>
                        <p class="text-sm text-zinc-700">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>

            {{-- Items --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100">
                    <flux:heading size="sm">Items</flux:heading>
                </div>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-zinc-50 border-b border-zinc-100">
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr class="border-b border-zinc-50">
                                <td class="px-6 py-3 text-sm text-zinc-800">
                                    {{ $item->product_name }}
                                    @unless ($item->product)
                                        <span class="text-xs text-zinc-400">(product removed)</span>
                                    @endunless
                                </td>
                                <td class="px-6 py-3 text-sm text-zinc-600 text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-zinc-600 text-right">{{ $item->quantity }}</td>
                                <td class="px-6 py-3 text-sm font-medium text-zinc-900 text-right">{{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-zinc-100">
                            <td colspan="3" class="px-6 py-3 text-sm font-semibold text-zinc-700 text-right">Subtotal</td>
                            <td class="px-6 py-3 text-sm font-semibold text-zinc-900 text-right">{{ number_format((float) $order->subtotal, 2) }} {{ $order->currency }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-6 py-3 text-sm font-bold text-zinc-800 text-right">Total</td>
                            <td class="px-6 py-3 text-sm font-bold text-zinc-900 text-right">{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Transactions --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100">
                    <flux:heading size="sm">Transactions</flux:heading>
                </div>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-zinc-50 border-b border-zinc-100">
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Paid At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->transactions as $transaction)
                            <tr class="border-b border-zinc-50">
                                <td class="px-6 py-3 font-mono text-xs text-zinc-700">{{ $transaction->reference }}</td>
                                <td class="px-6 py-3 text-sm text-zinc-600">{{ \App\Support\PaymentMethods::label($transaction->payment_method) }}</td>
                                <td class="px-6 py-3 text-sm text-zinc-900 text-right">{{ number_format((float) $transaction->amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-zinc-600">{{ ucfirst($transaction->status) }}</td>
                                <td class="px-6 py-3 text-xs text-zinc-500">{{ $transaction->paid_at?->format('M d, Y g:i A') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-zinc-500">No transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sidebar: status update --}}
        <div class="w-full lg:w-[320px] shrink-0 space-y-4">
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm p-6 space-y-4">
                <flux:heading size="sm">Update Order</flux:heading>

                <flux:field>
                    <flux:label>Fulfillment Status</flux:label>
                    <flux:select wire:model="status">
                        @foreach (\App\Models\Order::STATUSES as $s)
                            <flux:select.option value="{{ $s }}">{{ ucfirst($s) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                <flux:field>
                    <flux:label>Payment Status</flux:label>
                    <flux:select wire:model="paymentStatus">
                        @foreach (\App\Models\Order::PAYMENT_STATUSES as $s)
                            <flux:select.option value="{{ $s }}">{{ ucfirst($s) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="paymentStatus" />
                </flux:field>

                <flux:button variant="primary" wire:click="updateStatus" wire:loading.attr="disabled" class="w-full">
                    Save Changes
                </flux:button>
            </div>
        </div>

    </div>
</div>
