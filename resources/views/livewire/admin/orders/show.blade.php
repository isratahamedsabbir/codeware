<div class="max-w-[1200px] w-full mx-auto">

    @push('page-header-actions')
        <flux:button variant="primary" size="sm" icon="document-text" href="{{ route('admin.orders.invoice', $order) }}" target="_blank">
            View Invoice
        </flux:button>

        <flux:button variant="outline" size="sm" icon="printer" href="{{ route('admin.orders.address', $order) }}" target="_blank">
            Print Address
        </flux:button>

        @if ($order->items->contains(fn ($item) => $item->type === 'product' && $item->product?->hasWarranty()))
            <flux:button variant="outline" size="sm" icon="shield-check" href="{{ route('admin.orders.warranty', $order) }}" target="_blank">
                Warranty Card
            </flux:button>
        @endif

        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.orders') }}" wire:navigate>
            Back to Orders
        </flux:button>
    @endpush

    <div class="flex gap-5 items-start flex-wrap lg:flex-nowrap">

        {{-- Main --}}
        <div class="flex-1 min-w-0 space-y-5">

            {{-- Customer & shipping --}}
            <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm p-6">
                <flux:heading size="lg" class="mb-1">Order {{ $order->order_number }}</flux:heading>
                <p class="text-sm text-zinc-500 mb-5">Placed {{ $order->created_at->toDisplay() }}</p>

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
            <x-admin-section-card icon="shopping-bag" title="Items" body-class="">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Item</th>
                            <th class="px-6 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-6 py-3 text-sm text-zinc-800">
                                    {{ $item->item_name }}
                                    @if ($item->sku)
                                        <span class="block text-xs font-mono text-zinc-400">{{ $item->sku }}</span>
                                    @endif
                                    @if ($item->type === 'service')
                                        <span class="text-xs text-zinc-400">(service)</span>
                                    @endif
                                    @if ($item->type === 'product' && ! $item->product)
                                        <span class="text-xs text-zinc-400">(product removed)</span>
                                    @elseif ($item->type === 'service' && ! $item->service)
                                        <span class="text-xs text-zinc-400">(service removed)</span>
                                    @endif
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
            </x-admin-section-card>

            {{-- Transactions --}}
            <x-admin-section-card icon="banknotes" title="Transactions" icon-color="bg-emerald-500/10 text-emerald-600" body-class="">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Paid At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($order->transactions as $transaction)
                            <tr>
                                <td class="px-6 py-3 font-mono text-xs text-zinc-700">{{ $transaction->reference }}</td>
                                <td class="px-6 py-3 text-sm text-zinc-600">{{ \App\Support\PaymentMethods::label($transaction->payment_method) }}</td>
                                <td class="px-6 py-3 text-sm text-zinc-900 text-right">{{ number_format((float) $transaction->amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-zinc-600">{{ ucfirst($transaction->status) }}</td>
                                <td class="px-6 py-3 text-xs text-zinc-500">{{ $transaction->paid_at?->toDisplay() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-zinc-500">No transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-admin-section-card>
        </div>

        {{-- Sidebar: status update --}}
        <div class="w-full lg:w-[320px] shrink-0 space-y-4">
            <x-admin-section-card icon="pencil-square" title="Update Order" icon-color="bg-indigo-500/10 text-indigo-600">
                <flux:field>
                    <flux:label>Fulfillment Status</flux:label>
                    <flux:select wire:model="status">
                        @foreach (\App\Models\Order::STATUSES as $s)
                            <flux:select.option value="{{ $s }}"
                                :disabled="$s === 'cancelled' && $order->status !== 'cancelled' && ! $order->canBeCancelled()">
                                {{ ucfirst($s) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    @if ($order->status !== 'cancelled' && ! $order->canBeCancelled())
                        <p class="text-xs text-zinc-400">This order can no longer be cancelled — it has already reached "{{ $order->status }}".</p>
                    @endif
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

                <flux:button size="sm" variant="primary" wire:click="updateStatus" wire:loading.attr="disabled" class="w-full">
                    Save Changes
                </flux:button>
            </x-admin-section-card>
        </div>

    </div>
</div>
