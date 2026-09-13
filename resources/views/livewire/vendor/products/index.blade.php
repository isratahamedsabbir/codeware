@push('page-header-actions')
    <flux:button variant="primary" size="sm" icon="plus" href="{{ route('vendor.products.create') }}" wire:navigate>
        New Product
    </flux:button>
@endpush

<div class="admin-card overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search products…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">SKU</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Price</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Stock</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($products as $product)
                        <tr class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-4 py-2.5">
                                <div class="font-medium text-zinc-900 text-sm leading-snug">
                                    <x-truncate :text="$product->getTranslation('name', 'en', false)" />
                                </div>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="text-xs font-mono text-zinc-500">{{ $product->sku ?: '—' }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-sm text-zinc-700">
                                {{ number_format((float) $product->price, 2) }}
                                @if ($product->hasDiscount())
                                    <span class="ml-1 text-xs text-emerald-600">({{ number_format((float) $product->discount_price, 2) }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                @if ($product->inStock())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/10">
                                        {{ (int) $product->quantity }} in stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 ring-1 ring-rose-600/10">
                                        Out of stock
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                @if ($product->trashed())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-50 text-zinc-500 ring-1 ring-zinc-600/10">Deleted</span>
                                @elseif ($product->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/10">Active</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-50 text-zinc-500 ring-1 ring-zinc-600/10">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                @unless ($product->trashed())
                                    <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('vendor.products.edit', $product->id) }}" wire:navigate>
                                        Edit
                                    </flux:button>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <flux:icon.cube class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                <p class="text-sm text-zinc-600">No products yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="px-6 py-3">
        {{ $products->links() }}
    </div>

</div>
