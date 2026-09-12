<div class="max-w-4xl space-y-4">

    <flux:button variant="ghost" size="sm" icon="arrow-left" href="{{ route('vendor.orders') }}" wire:navigate>
        Back
    </flux:button>

    <div class="rounded-[5px] border border-zinc-200 bg-white p-5 shadow-sm">
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
                <p class="text-sm text-zinc-800">{{ $order->customer_name }}</p>
                <p class="text-sm text-zinc-500">{{ $order->customer_email }}</p>
                <p class="text-sm text-zinc-500">{{ $order->customer_phone }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-500 mb-1">Shipping Address</p>
                <p class="text-sm text-zinc-800">{{ $order->shipping_address }}</p>
            </div>
        </div>

        <div class="mt-5 pt-5 border-t border-zinc-100 text-xs text-zinc-500">
            Placed {{ $order->created_at?->toDisplay() }}
        </div>
    </div>

    <div class="rounded-[5px] border border-zinc-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-zinc-100">
            <flux:heading size="sm">Your Items</flux:heading>
            <flux:text class="text-xs text-zinc-500">Only the line items from your vendor(s) in this order are shown here.</flux:text>
        </div>
        <table class="w-full divide-y divide-gray-200">
            <thead>
                <tr class="bg-zinc-50">
                    <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Product</th>
                    <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Unit Price</th>
                    <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Qty</th>
                    <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Line Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($items as $item)
                    <tr>
                        <td class="px-4 py-2.5 text-sm text-zinc-800">{{ $item->product_name }}</td>
                        <td class="px-4 py-2.5 text-sm text-zinc-600">{{ $order->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="px-4 py-2.5 text-sm text-zinc-600">{{ $item->quantity }}</td>
                        <td class="px-4 py-2.5 text-sm font-medium text-zinc-900">{{ $order->currency }} {{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-zinc-50">
                    <td colspan="3" class="px-4 py-2.5 text-right text-sm font-medium text-zinc-600">Your Total</td>
                    <td class="px-4 py-2.5 text-sm font-semibold text-zinc-900">{{ $order->currency }} {{ number_format((float) $items->sum('line_total'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>
