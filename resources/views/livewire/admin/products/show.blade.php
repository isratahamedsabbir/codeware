<div class="max-w-[1200px] w-full mx-auto space-y-5">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('admin.products.edit', $product->id) }}" wire:navigate>
            Edit
        </flux:button>
        <flux:button variant="ghost" size="sm" icon="tag" href="{{ route('admin.products.label', $product->id) }}">
            Print Label
        </flux:button>
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.products') }}" wire:navigate>
            Back to Products
        </flux:button>
    @endpush

    {{-- Product details --}}
    <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <flux:heading size="lg" class="mb-1">{{ $product->getTranslation('name', 'en', false) }}</flux:heading>
                @if ($product->slug)
                    <x-copy-text :text="$product->slug" class="font-mono text-xs text-zinc-500">{{ $product->slug }}</x-copy-text>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if ($product->trashed())
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-500">Deleted</span>
                @elseif ($product->status === 'active')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200">Active</span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200">Inactive</span>
                @endif
                @if ($product->is_featured)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Featured</span>
                @endif
                @if ($product->is_upcoming)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-violet-50 text-violet-700 border border-violet-200">Upcoming</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 mt-6 pt-6 border-t border-zinc-100">
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Price</h3>
                <p class="text-sm text-zinc-800">
                    {{ number_format((float) $product->price, 2) }}
                    @if ($product->hasDiscount())
                        <span class="text-xs text-emerald-600">({{ number_format((float) $product->discount_price, 2) }})</span>
                    @endif
                </p>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Stock</h3>
                <p class="text-sm text-zinc-800">{{ $product->inStock() ? (int) $product->quantity.' in stock' : 'Out of stock' }}</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">SKU</h3>
                <p class="text-sm text-zinc-800 font-mono">{{ $product->sku ?: '—' }}</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Type</h3>
                <p class="text-sm text-zinc-800">{{ $product->product_type === 'digital' ? 'Digital' : 'Physical' }}</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Category</h3>
                <p class="text-sm text-zinc-800">{{ $product->categories->isNotEmpty() ? $product->categories->map(fn ($c) => $c->getTranslation('name', 'en', false))->implode(', ') : '—' }}</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Brand</h3>
                <p class="text-sm text-zinc-800">{{ $product->brand?->name ?? '—' }}</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Vendor</h3>
                <p class="text-sm text-zinc-800">{{ $product->vendor?->name ?? '—' }}</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Shipping</h3>
                <p class="text-sm text-zinc-800">{{ $product->charge_shipping ? 'Charged' : 'Free' }}</p>
            </div>
        </div>
    </div>

    {{-- Orders --}}
    <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between gap-3 p-4 flex-wrap border-b border-zinc-100">
            <flux:heading size="sm">Orders</flux:heading>
            <div class="relative max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by order number…"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Order #</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Qty</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Line Total</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Placed</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        @php $item = $order->items->first(); @endphp
                        <tr class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-4 py-2.5">
                                <span class="text-sm font-mono text-zinc-700">{{ $order->order_number }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-sm text-zinc-700">{{ $order->customer_name }}</td>
                            <td class="px-4 py-2.5 text-sm text-zinc-600">{{ $item?->quantity }}</td>
                            <td class="px-4 py-2.5 text-sm font-medium text-zinc-900">{{ $order->currency }} {{ number_format((float) $item?->line_total, 2) }}</td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-medium
                                    {{ match ($order->status) {
                                        'delivered' => 'bg-green-50 text-green-700',
                                        'cancelled' => 'bg-rose-50 text-rose-700',
                                        'shipped' => 'bg-indigo-50 text-indigo-700',
                                        'processing' => 'bg-cyan-50 text-cyan-700',
                                        default => 'bg-amber-50 text-amber-700',
                                    } }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-zinc-500">{{ $order->created_at?->toDisplay() }}</td>
                            <td class="px-4 py-2.5">
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.orders.show', $order->id), 'icon' => 'eye', 'label' => 'View', 'color' => 'secondary'],
                                ]" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <flux:icon.shopping-bag class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                <p class="text-sm text-zinc-600">No orders for this product yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-3">
            {{ $orders->links() }}
        </div>
    </div>

</div>
